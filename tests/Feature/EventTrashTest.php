<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('a deleted event can be restored from the index with its prizes and participants', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['name' => '秋の宝探し']);
    $prize = Prize::factory()->for($event)->create();
    $participant = Participant::factory()->for($event)->create();
    $prize->forceFill(['participant_id' => $participant->id, 'acquired_at' => now()])->save();

    $this->actingAs($user)->delete(route('events.destroy', $event))
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', 'イベントを削除しました。30日以内なら、「最近削除したイベント」から戻せます。');

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('最近削除したイベント（1件）')
        ->assertSee(route('events.trash'))
        ->assertDontSee('data-restore-button', false);
    $this->get(route('events.trash'))
        ->assertOk()
        ->assertSee('秋の宝探し')
        ->assertSee('data-restore-button', false);
    $this->get(route('events.show', $event))->assertNotFound();

    $this->post(route('events.restore', $event->id))->assertRedirect(route('events.index'))->assertSessionHas('status', '「秋の宝探し」を戻しました。');

    expect($event->fresh()->trashed())->toBeFalse()
        ->and($prize->fresh()->participant_id)->toBe($participant->id);
    $this->get(route('events.show', $event))->assertOk();
    $this->get(route('events.index'))->assertDontSee('data-trash-link', false);
    $this->get(route('events.trash'))->assertSee('最近削除したイベントはありません。');
});

test('while an event is deleted its QR codes, join link, cards and participants stop working', function () {
    $event = Event::factory()->create();
    $prize = Prize::factory()->for($event)->create();
    $participant = Participant::factory()->for($event)->create();
    $card = Participant::factory()->for($event)->create();
    $card->forceFill(['card_token' => str_repeat('c', 40)])->save();

    $event->delete();

    $this->get(route('treasure.show', $prize->token))->assertNotFound();
    $this->get(route('join', $event->join_token))->assertNotFound();
    // ログイン状態は参加者を読み直して確かめるので、見つからない＝ログアウトになる
    expect(Participant::find($participant->id))->toBeNull()
        ->and(Prize::find($prize->id))->toBeNull();

    $this->actingAs($event->user)->get(route('cards.play', $card->card_token))->assertNotFound();
    $this->actingAs($event->user)->get(route('redemption.lookup', $participant->redeem_token))->assertNotFound();
});

test('deleted events are removed for good after thirty days', function () {
    $user = User::factory()->create();
    $old = Event::factory()->for($user)->create();
    Prize::factory()->for($old)->create();
    $recent = Event::factory()->for($user)->create();
    $old->delete();

    $this->travel(20)->days();
    $recent->delete();
    $this->travel(11)->days();

    $this->actingAs($user)->get(route('events.index'))->assertOk()->assertSee('最近削除したイベント（1件）');
    $this->get(route('events.trash'))->assertOk()->assertSee('data-restore-button', false);

    expect(Event::withTrashed()->find($old->id))->toBeNull()
        ->and(Prize::withoutGlobalScopes()->where('event_id', $old->id)->exists())->toBeFalse()
        ->and(Event::withTrashed()->find($recent->id))->not->toBeNull();
});

test('organizers cannot restore other organizers events or expired ones', function () {
    $user = User::factory()->create();
    $foreign = Event::factory()->create();
    $foreign->delete();
    $mine = Event::factory()->for($user)->create();
    $mine->delete();
    Event::withTrashed()->whereKey($mine->id)->update(['deleted_at' => now()->subDays(31)]);

    $this->actingAs($user)->post(route('events.restore', $foreign->id))->assertNotFound();
    $this->post(route('events.restore', $mine->id))->assertNotFound();

    expect($foreign->fresh()->trashed())->toBeTrue();
});

test('the event list folds when there are five to ten events and adds five at a time from eleven', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $make = fn (int $count) => collect(range(1, $count))->each(fn ($i) => Event::factory()->for($user)->create(['name' => "イベント{$i}", 'created_at' => now()->subMinutes(100 - $i)]));

    $make(4);
    $this->get(route('events.index'))->assertDontSee('data-more-events', false)->assertDontSee('data-show-more-events', false);

    $make(3); // 7件
    $this->get(route('events.index'))
        ->assertSee('data-more-events', false)
        ->assertSee('残り3件を表示');

    $make(5); // 12件
    $response = $this->get(route('events.index'));
    $response->assertDontSee('data-more-events', false)->assertSee('さらに5件を表示（残り7件）');
    expect(substr_count($response->getContent(), 'data-selection-card'))->toBe(5);

    $response = $this->get(route('events.index', ['show' => 10]));
    $response->assertSee('さらに2件を表示（残り2件）');
    expect(substr_count($response->getContent(), 'data-selection-card'))->toBe(10);

    $response = $this->get(route('events.index', ['show' => 15]));
    $response->assertDontSee('data-show-more-events', false)->assertSee('data-close-events', false);
    $this->get(route('events.index', ['show' => 10]))->assertDontSee('data-close-events', false);
    expect(substr_count($response->getContent(), 'data-selection-card'))->toBe(12)
        ->and(substr_count($this->get(route('events.index', ['show' => -3]))->getContent(), 'data-selection-card'))->toBe(5);
});

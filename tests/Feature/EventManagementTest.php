<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('organizers can create an event', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('events.store'), [
        'name' => '秋の宝探し',
        'description' => '30分勝負',
    ]);

    $event = $user->events()->sole();
    $response->assertRedirect(route('events.show', $event));
    expect($event->join_token)->toHaveLength(40);
});

test('organizers cannot see or change another organizers event', function () {
    $event = Event::factory()->create();
    $prize = Prize::factory()->for($event)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder);

    $this->get(route('events.show', $event))->assertForbidden();
    $this->get(route('events.qrcodes', $event))->assertForbidden();
    $this->put(route('events.update', $event), ['name' => 'hacked'])->assertForbidden();
    $this->post(route('events.reset-acquisitions', $event))->assertForbidden();
    $this->post(route('events.end', $event))->assertForbidden();
    $this->get(route('events.prizes.show', [$event, $prize]))->assertForbidden();
    $this->delete(route('events.prizes.destroy', [$event, $prize]))->assertForbidden();

    expect($prize->fresh())->not->toBeNull();
});

test('a prize from another event cannot be reached through this events url', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $otherPrize = Prize::factory()->create();

    $this->actingAs($user)
        ->get(route('events.prizes.show', [$event, $otherPrize]))
        ->assertNotFound();
});

test('prizes get sequential numbers within their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->create();

    $this->actingAs($user);
    $this->post(route('events.prizes.store', $event), ['name' => 'お菓子'])->assertRedirect(route('events.show', $event));
    $this->post(route('events.prizes.store', $event), ['is_miss' => '1'])->assertRedirect();

    expect($event->prizes()->orderBy('number')->get(['number', 'name', 'is_miss'])->toArray())->toBe([
        ['number' => 1, 'name' => 'お菓子', 'is_miss' => false],
        ['number' => 2, 'name' => 'ハズレ', 'is_miss' => true],
    ]);
});

test('the remaining card shows only prizes that have not been won yet', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();
    $event->prizes()->create(['name' => 'りんごジュース']);
    $event->prizes()->create(['name' => 'からっぽ', 'is_miss' => true]);
    Prize::factory()->for($event)->takenBy($participant)->create(['name' => 'りんご飴']);

    $this->actingAs($user);

    $this->get(route('events.show', $event))
        ->assertSee(['りんごジュース', 'からっぽ', 'りんご飴'])
        ->assertSee('href="'.route('events.show', [$event, 'stock' => 'available']).'#prize-list"', false)
        ->assertDontSee('id="search-name"', false);

    $this->get(route('events.show', [$event, 'stock' => 'available']))
        ->assertSee('りんごジュース')
        ->assertDontSee('りんご飴')
        ->assertDontSee('からっぽ')
        ->assertSee('まだ獲得されていない景品だけを表示しています');
});

test('the qr code pages embed the treasure and join urls without revealing prize names', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $prize = Prize::factory()->for($event)->create(['name' => '秘密の景品']);

    $this->actingAs($user);

    $this->get(route('events.qrcodes', $event))
        ->assertOk()
        ->assertSee('data-qr="'.route('treasure.show', $prize).'"', false)
        ->assertDontSee('秘密の景品');

    $this->get(route('events.join-qrcode', $event))
        ->assertOk()
        ->assertSee('data-qr="'.route('join', $event).'"', false);
});

test('deleting an event removes its prizes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->for($event)->create();

    $this->actingAs($user)->delete(route('events.destroy', $event))->assertRedirect(route('events.index'));

    expect(Prize::count())->toBe(0);
});

test('organizers can delete the selected prizes at once', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    [$first, $second, $kept] = Prize::factory()->count(3)->for($event)->create();

    $this->actingAs($user)
        ->delete(route('events.prizes.destroy-many', $event), ['prize_ids' => [$first->id, $second->id]])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', '選択した景品を2件削除しました。');

    expect($event->prizes()->pluck('id')->all())->toBe([$kept->id]);
});

test('bulk deleting ignores prizes that belong to another event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $otherPrize = Prize::factory()->create();

    $this->actingAs($user)
        ->delete(route('events.prizes.destroy-many', $event), ['prize_ids' => [$otherPrize->id]])
        ->assertRedirect();

    expect($otherPrize->fresh())->not->toBeNull();
});

test('bulk deleting requires at least one selected prize', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('events.prizes.destroy-many', $event))
        ->assertSessionHasErrors('prize_ids');
});

test('organizers cannot bulk delete another organizers prizes', function () {
    $prize = Prize::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('events.prizes.destroy-many', $prize->event), ['prize_ids' => [$prize->id]])
        ->assertForbidden();

    expect($prize->fresh())->not->toBeNull();
});

test('resetting acquisitions puts every prize back in stock', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->count(2)->for($event)->takenBy($participant)->create();
    $untouched = Prize::factory()->for($event)->create();
    $otherEventPrize = Prize::factory()->takenBy(Participant::factory()->create())->create();

    $this->actingAs($user)
        ->post(route('events.reset-acquisitions', $event))
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', '景品の獲得記録をリセットしました（2件を未獲得に戻しました）。参加者とタイマーはそのままです。');

    expect($event->prizes()->whereNotNull('participant_id')->count())->toBe(0)
        ->and($event->prizes()->whereNotNull('acquired_at')->count())->toBe(0)
        ->and($untouched->fresh()->participant_id)->toBeNull()
        ->and($otherEventPrize->fresh()->participant_id)->not->toBeNull();
});

test('the create form can be prefilled from a past event', function () {
    $user = User::factory()->create();
    $source = Event::factory()->for($user)->create(['name' => '春の宝探し', 'description' => '30分勝負']);

    $this->actingAs($user)
        ->get(route('events.create', ['copy_from' => $source->id]))
        ->assertOk()
        ->assertSee('value="春の宝探し"', false)
        ->assertSee('30分勝負')
        ->assertSee('name="copy_from" value="'.$source->id.'"', false);
});

test('creating from a past event copies its prizes as fresh, untaken prizes', function () {
    $user = User::factory()->create();
    $source = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($source)->create();
    $taken = Prize::factory()->for($source)->takenBy($participant)->create(['name' => 'お菓子', 'description' => '受付で交換']);
    $miss = Prize::factory()->for($source)->miss()->create();

    $this->actingAs($user)->post(route('events.store'), [
        'name' => '秋の宝探し',
        'copy_from' => $source->id,
        'copy_prizes' => '1',
    ])->assertRedirect();

    $event = $user->events()->where('name', '秋の宝探し')->sole();
    $copies = $event->prizes()->orderBy('number')->get();

    expect($copies)->toHaveCount(2)
        ->and($copies->pluck('number')->all())->toBe([$taken->number, $miss->number])
        ->and($copies[0]->only(['name', 'description', 'is_miss']))->toBe(['name' => 'お菓子', 'description' => '受付で交換', 'is_miss' => false])
        ->and($copies[1]->is_miss)->toBeTrue()
        ->and($copies->whereNotNull('participant_id'))->toBeEmpty()
        ->and($copies->pluck('token')->intersect([$taken->token, $miss->token]))->toBeEmpty()
        ->and($source->prizes()->count())->toBe(2)
        ->and($taken->fresh()->participant_id)->toBe($participant->id);
});

test('prizes are not copied when the organizer opts out', function () {
    $user = User::factory()->create();
    $source = Event::factory()->for($user)->create();
    Prize::factory()->for($source)->create();

    $this->actingAs($user)->post(route('events.store'), [
        'name' => '景品なし',
        'copy_from' => $source->id,
        'copy_prizes' => '0',
    ]);

    expect($user->events()->where('name', '景品なし')->sole()->prizes()->count())->toBe(0);
});

test('organizers cannot copy another organizers event', function () {
    $user = User::factory()->create();
    $foreign = Event::factory()->create(['name' => '他人のイベント']);
    Prize::factory()->for($foreign)->create();

    $this->actingAs($user)
        ->get(route('events.create', ['copy_from' => $foreign->id]))
        ->assertOk()
        ->assertDontSee('他人のイベント');

    $this->actingAs($user)->post(route('events.store'), [
        'name' => '乗っ取り',
        'copy_from' => $foreign->id,
        'copy_prizes' => '1',
    ])->assertSessionHasErrors('copy_from');

    expect($user->events()->count())->toBe(0);
});

test('the treasure qr print page offers three print sizes', function (?string $requested, string $expectedGrid) {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->for($event)->create();

    $this->actingAs($user)
        ->get(route('events.qrcodes', array_filter([$event, 'size' => $requested])))
        ->assertOk()
        ->assertSee($expectedGrid)
        ->assertSee('3×3枚')
        ->assertSee('4×4枚')
        ->assertSee('5×6枚');
})->with([
    'default is large' => [null, 'print:grid-cols-3'],
    'medium' => ['medium', 'print:grid-cols-4'],
    'small' => ['small', 'print:grid-cols-5'],
    'unknown falls back to large' => ['huge', 'print:grid-cols-3'],
]);

test('organizers can delete the selected events at once along with their prizes', function () {
    $user = User::factory()->create();
    [$first, $second, $kept] = Event::factory()->count(3)->for($user)->create();
    Prize::factory()->for($first)->create();

    $this->actingAs($user)
        ->delete(route('events.destroy-many'), ['event_ids' => [$first->id, $second->id]])
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', '選択したイベントを2件削除しました。30日以内なら、下の「最近削除したイベント」から戻せます。');

    expect($user->events()->pluck('id')->all())->toBe([$kept->id])
        ->and(Prize::where('event_id', $first->id)->exists())->toBeFalse();
});

test('bulk deleting events ignores events owned by another organizer', function () {
    $user = User::factory()->create();
    $foreign = Event::factory()->create();

    $this->actingAs($user)
        ->delete(route('events.destroy-many'), ['event_ids' => [$foreign->id]])
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', '選択したイベントを0件削除しました。30日以内なら、下の「最近削除したイベント」から戻せます。');

    expect($foreign->fresh())->not->toBeNull();
});

test('bulk deleting events requires at least one selection', function () {
    $this->actingAs(User::factory()->create())
        ->delete(route('events.destroy-many'))
        ->assertSessionHasErrors('event_ids');
});

test('the join qr page offers large, medium and small print sizes', function (?string $requested, string $qrWidth, string $orientation, int $copies) {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('events.join-qrcode', array_filter([$event, 'size' => $requested])))
        ->assertOk()
        ->assertSee('A4いっぱい')
        ->assertSee('A4中央に配置')
        ->assertSee('A4横に2枚')
        ->assertSee($qrWidth)
        ->assertSee("size: A4 {$orientation};", false);

    expect(substr_count($response->getContent(), 'data-qr="'.route('join', $event).'"'))->toBe($copies);
})->with([
    'default is large' => [null, 'print:w-[150mm]', 'portrait', 1],
    'medium' => ['medium', 'print:w-[110mm]', 'portrait', 1],
    'small fits two on a landscape sheet' => ['small', 'print:w-[85mm]', 'landscape', 2],
    'unknown falls back to large' => ['huge', 'print:w-[150mm]', 'portrait', 1],
]);

test('the event page counts the remaining winning prizes, excluding misses', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->count(3)->for($event)->create();
    Prize::factory()->for($event)->takenBy($participant)->create();
    Prize::factory()->count(2)->for($event)->miss()->create();

    $response = $this->actingAs($user)->get(route('events.show', $event))->assertOk();

    expect($response->viewData('event'))
        ->winning_prizes_count->toBe(4)
        ->remaining_prizes_count->toBe(3);
    $response->assertSeeInOrder(['まだ見つかっていない', '3']);
});

test('the event list shows the remaining winning prizes for each event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->count(2)->for($event)->create();
    Prize::factory()->for($event)->takenBy($participant)->create();
    Prize::factory()->for($event)->miss()->create();

    $response = $this->actingAs($user)->get(route('events.index'))->assertOk();

    expect($response->viewData('events')->sole())
        ->winning_prizes_count->toBe(3)
        ->remaining_prizes_count->toBe(2);
    $response->assertSeeInOrder(['残り', '2個']);
});

test('misses are left out of the stock column and the stock filters', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $event->prizes()->create(['name' => '未取得の景品']);
    Prize::factory()->for($event)->miss()->create(['name' => 'ハズレ景品']);

    $this->actingAs($user);

    $this->get(route('events.show', [$event, 'stock' => 'available']))
        ->assertSee('未取得の景品')
        ->assertDontSee('ハズレ景品');

    $this->get(route('events.show', [$event, 'stock' => 'taken']))
        ->assertDontSee('ハズレ景品');

    $this->get(route('events.show', $event))
        ->assertSee('ハズレ景品')
        ->assertSee('title="ハズレは在庫の対象外です">対象外</span>', false);
});

test('ending an event logs every participant out and resets the prizes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    [$player, $other] = Participant::factory()->count(2)->for($event)->create();
    $prize = Prize::factory()->for($event)->takenBy($player)->create();
    $otherEventPlayer = Participant::factory()->create();

    $this->actingAs($user)
        ->post(route('events.end', $event))
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', 'イベントを片付けました。参加者2人の記録を消し、景品をすべて未獲得に戻しました。景品とQRコードは、次の回にそのまま使えます。');

    expect($event->participants()->count())->toBe(0)
        ->and($prize->fresh())
        ->participant_id->toBeNull()
        ->acquired_at->toBeNull()
        ->and($otherEventPlayer->fresh())->not->toBeNull();
});

test('a participant who joined through the join page is logged out when the event ends', function () {
    $event = Event::factory()->create();

    $this->post(route('join.store', $event), ['nickname' => 'たろう'])->assertRedirect(route('play.index'));
    $this->get(route('play.index'))->assertOk()->assertSee('たろう');

    $event->end();
    $this->app['auth']->forgetGuards();

    $this->get(route('play.index'))->assertRedirect(route('participant.required'));
});

test('organizers can set, change and clear the prize limit per participant', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('events.store'), ['name' => '上限あり', 'max_prizes_per_participant' => '3']);
    $event = $user->events()->sole();
    expect($event->max_prizes_per_participant)->toBe(3);

    $this->get(route('events.show', $event))->assertSee('1人3個まで');

    $this->put(route('events.update', $event), ['name' => '上限あり', 'max_prizes_per_participant' => '']);
    expect($event->fresh()->max_prizes_per_participant)->toBeNull();
});

test('the prize limit must be a positive whole number', function (string $value) {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), ['name' => 'テスト', 'max_prizes_per_participant' => $value])
        ->assertSessionHasErrors('max_prizes_per_participant');
})->with(['0', '-1', '1.5', 'abc', '1000']);

test('the prize limit is carried over when creating from a past event', function () {
    $user = User::factory()->create();
    $source = Event::factory()->for($user)->create(['max_prizes_per_participant' => 4]);

    $this->actingAs($user)
        ->get(route('events.create', ['copy_from' => $source->id]))
        ->assertSee('name="max_prizes_per_participant" type="number" min="1" max="999" inputmode="numeric" value="4"', false);
});

test('the miss setting defaults to not counting misses', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('events.create'))
        ->assertSee('name="count_misses_toward_limit" value="0" class="mt-1 h-4 w-4 accent-sea" checked', false);

    $this->post(route('events.store'), ['name' => '設定なし', 'max_prizes_per_participant' => '2']);

    expect($user->events()->sole()->count_misses_toward_limit)->toBeFalse();
});

test('organizers can choose to count misses toward the limit', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('events.store'), [
        'name' => 'ハズレも数える',
        'max_prizes_per_participant' => '3',
        'count_misses_toward_limit' => '1',
    ]);
    $event = $user->events()->sole();

    expect($event->count_misses_toward_limit)->toBeTrue();
    $this->get(route('events.show', $event))->assertSee('1人3個まで（ハズレも含む）');

    $this->put(route('events.update', $event), ['name' => 'ハズレも数える', 'max_prizes_per_participant' => '3', 'count_misses_toward_limit' => '0']);
    expect($event->fresh()->count_misses_toward_limit)->toBeFalse();
});

test('the miss setting is carried over when creating from a past event', function () {
    $user = User::factory()->create();
    $source = Event::factory()->for($user)->withPrizeLimit(2, countMisses: true)->create();

    $this->actingAs($user)
        ->get(route('events.create', ['copy_from' => $source->id]))
        ->assertSee('name="count_misses_toward_limit" value="1" class="mt-1 h-4 w-4 accent-sea" checked', false);
});

test('organizers can open the participant list from the event page', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $hanako = Participant::factory()->for($event)->create(['nickname' => 'はなこ']);
    Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    Prize::factory()->for($event)->create(['participant_id' => $hanako->id]);
    Participant::factory()->create(['nickname' => 'よそのひと']);

    $this->actingAs($organizer)
        ->get(route('events.show', $event))
        ->assertSee('href="'.route('events.participants', $event).'"', false);

    $this->actingAs($organizer)
        ->get(route('events.participants', $event))
        ->assertOk()
        ->assertSeeInOrder(['はなこ', 'たろう'])
        ->assertDontSee('よそのひと');
});

test('the participant label is not a link when nobody has joined yet', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();

    $this->actingAs($organizer)
        ->get(route('events.show', $event))
        ->assertDontSee(route('events.participants', $event));
});

test('organizers cannot see participants of other organizers\' events', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.participants', $event))
        ->assertForbidden();
});

test('organizers can see which prizes each participant has won', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $hanako = Participant::factory()->for($event)->create(['nickname' => 'はなこ']);
    $taro = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    Prize::factory()->for($event)->takenBy($hanako)->create(['name' => 'りんご飴']);
    Prize::factory()->for($event)->takenBy($taro)->create(['name' => 'みかんゼリー']);

    $this->actingAs($organizer)
        ->get(route('events.participants', $event))
        ->assertSee(route('events.participants.show', [$event, $hanako]));

    $this->get(route('events.participants.show', [$event, $hanako]))
        ->assertOk()
        ->assertSee('はなこ')
        ->assertSee('りんご飴')
        ->assertDontSee('みかんゼリー');
});

test('participant pages are only reachable through their own event and organizer', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $otherEvent = Event::factory()->for($organizer)->create();
    $stranger = Participant::factory()->for($otherEvent)->create();

    $this->actingAs($organizer)
        ->get(route('events.participants.show', [$event, $stranger]))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('events.participants.show', [$otherEvent, $stranger]))
        ->assertForbidden();
});

test('participants with the same nickname can be told apart by their participant id', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $first = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    $second = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    Participant::factory()->for($event)->create(['nickname' => 'はなこ']);
    Prize::factory()->for($event)->takenBy($second)->create(['name' => 'りんご飴']);

    expect($event->duplicatedNicknames())->toBe(['たろう'])
        ->and($first->displayId())->not->toBe($second->displayId());

    $this->actingAs($organizer)
        ->get(route('events.participants', $event))
        ->assertSee('同じニックネームの参加者がいます')
        ->assertSee([$first->displayId(), $second->displayId()])
        ->assertSee('同名あり');

    $this->get(route('events.show', $event))->assertSee($second->displayId());

    $this->actingAs($second, 'participant')
        ->get(route('play.index'))
        ->assertSee('あなたの参加者ID')
        ->assertSee($second->displayId());
});

test('each event card on the event list links to its qr code print pages', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create(['name' => '秋の宝探し']);

    $this->actingAs($organizer)
        ->get(route('events.index'))
        ->assertSee('href="'.route('events.join-qrcode', $event).'"', false)
        ->assertSee('href="'.route('events.qrcodes', $event).'"', false);

    foreach (['events.join-qrcode' => '参加用QRコード', 'events.qrcodes' => 'お宝用QRコード印刷'] as $page => $label) {
        $this->get(route($page, $event))
            ->assertSee('aria-label="パンくずリスト"', false)
            ->assertSeeInOrder(['イベント一覧', '秋の宝探し', $label])
            ->assertSee('href="'.route('events.index').'"', false)
            ->assertSee('href="'.route('events.show', $event).'"', false);
    }
});

test('the prize page returns to the prize list it was opened from', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create(['name' => '秋の宝探し']);
    $prize = Prize::factory()->for($event)->create();

    $this->actingAs($organizer);

    $this->get(route('events.show', [$event, 'stock' => 'available']))
        ->assertSee(e(route('events.prizes.show', [$event, $prize, 'back' => 'remaining'])), false);

    $this->get(route('events.prizes.show', [$event, $prize, 'back' => 'remaining']))
        ->assertSeeInOrder(['イベント一覧', '秋の宝探し', '景品 No.'.$prize->number])
        ->assertSee(e(route('events.show', [$event, 'stock' => 'available'])).'#prize-list', false);

    $this->get(route('events.prizes.show', [$event, $prize]))
        ->assertSee(route('events.show', $event).'#prize-list', false);

    $this->put(route('events.prizes.update', [$event, $prize]), ['name' => '新しい景品', 'back' => 'remaining'])
        ->assertRedirect(route('events.prizes.show', [$event, $prize, 'back' => 'remaining']));
});

test('the participant prize page lists prizes without detail links or an event name heading', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create(['name' => '秋の宝探し']);
    $hanako = Participant::factory()->for($event)->create(['nickname' => 'はなこ']);
    $prize = Prize::factory()->for($event)->takenBy($hanako)->create(['name' => 'りんご飴']);

    $this->actingAs($organizer)
        ->get(route('events.participants.show', [$event, $hanako]))
        ->assertSee('りんご飴')
        ->assertDontSee('href="'.route('events.prizes.show', [$event, $prize]).'"', false)
        ->assertDontSee('<p class="text-sm font-bold text-ink-soft">秋の宝探し</p>', false);
});

test('winner names on the prize list and prize page link to the participant prize page', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $hanako = Participant::factory()->for($event)->create(['nickname' => 'はなこ']);
    $prize = Prize::factory()->for($event)->takenBy($hanako)->create();
    $link = 'href="'.route('events.participants.show', [$event, $hanako]).'"';

    $this->actingAs($organizer)->get(route('events.show', $event))->assertSee($link, false);
    $this->get(route('events.prizes.show', [$event, $prize]))->assertSee($link, false);
});

test('the participant prize page shows breadcrumbs back to the event and the participant list', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create(['name' => '秋の宝探し']);
    $participant = Participant::factory()->for($event)->create(['nickname' => 'たろう']);

    $this->actingAs($organizer)
        ->get(route('events.participants.show', [$event, $participant]))
        ->assertSeeInOrder(['イベント一覧', '秋の宝探し', '参加者一覧', 'たろう さん'])
        ->assertSee('href="'.route('events.index').'"', false)
        ->assertSee('href="'.route('events.show', $event).'"', false)
        ->assertSee('href="'.route('events.participants', $event).'"', false)
        ->assertSee('aria-current="page"', false);
});

test('the qr code print pages have a cancel button back to the event page', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();

    foreach (['events.join-qrcode', 'events.qrcodes'] as $page) {
        $this->actingAs($organizer)
            ->get(route($page, $event))
            ->assertSeeInOrder(['href="'.route('events.show', $event).'" class="btn-secondary">キャンセル</a>', '印刷する'], false);
    }
});

test('the prize list marks won prizes as redeemed or not yet redeemed', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->for($event)->takenBy($participant)->create()->forceFill(['redeemed_at' => now()])->save();
    Prize::factory()->for($event)->takenBy($participant)->create();

    $this->actingAs($organizer)
        ->get(route('events.show', $event))
        ->assertSee('data-prize-status="引き換え済み"', false)
        ->assertSee('data-prize-status="引き換え待ち"', false)
        ->assertSee('>引き換え済み</span>', false)
        ->assertSee('>引き換え待ち</span>', false);
});

test('turning a prize into a miss while editing also renames it to ハズレ', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $prize = Prize::factory()->for($event)->create(['name' => 'お菓子セット']);

    $this->actingAs($organizer)
        ->put(route('events.prizes.update', [$event, $prize]), ['name' => 'お菓子セット', 'is_miss' => '1'])
        ->assertRedirect();

    expect($prize->fresh())->is_miss->toBeTrue()->name->toBe('ハズレ');

    $this->put(route('events.prizes.update', [$event, $prize]), ['name' => 'お菓子セット', 'is_miss' => '0']);
    expect($prize->fresh())->is_miss->toBeFalse()->name->toBe('お菓子セット');
});

test('the qr code print pages recommend printing with chrome', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();

    foreach (['events.join-qrcode', 'events.qrcodes'] as $page) {
        $this->actingAs($organizer)
            ->get(route($page, $event))
            ->assertSee('印刷は Google Chrome がおすすめです。');
    }
});

test('the event page puts the day-of operations first once the event is running', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['time_limit_minutes' => 30]);
    Prize::factory()->for($event)->create(['name' => 'とても長い名前の景品・お菓子の詰め合わせ（大袋）']);

    $this->actingAs($user);

    // 準備中：準備の操作が開いていて、引き換えのボタンはまだ出さない
    $this->get(route('events.show', $event))
        ->assertSee('data-phase="prep"', false)
        ->assertSee('data-setup-open="yes"', false)
        ->assertDontSee('data-redeem-bar', false)
        ->assertSeeInOrder(['data-setup', '宝探しスタート', '景品一覧']);

    // 開催中：残り時間 → 引き換え → 景品一覧 → たたんだ準備・設定
    $event->startTimer();
    $this->get(route('events.show', $event))
        ->assertSee('data-phase="live"', false)
        ->assertSee('data-redeem-bar', false)
        ->assertSee('data-setup-open="no"', false)
        ->assertSeeInOrder(['残り', 'data-redeem-button', '景品一覧', 'data-setup', 'data-danger-zone'])
        ->assertDontSee('本番の前に試してみる');

    // 締切後：引き換えのボタンはそのまま
    $event->stopTimer();
    $this->get(route('events.show', $event))
        ->assertSee('data-phase="closed"', false)
        ->assertSee('宝探しは締め切りました')
        ->assertSee('data-redeem-bar', false);
});

test('prizes can be filtered by those waiting to be handed over', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->for($event)->create(['name' => 'まだの景品']);
    Prize::factory()->for($event)->takenBy($participant)->create(['name' => '待っている景品']);
    $done = Prize::factory()->for($event)->takenBy($participant)->create(['name' => '渡した景品']);
    $done->forceFill(['redeemed_at' => now()])->save();

    $this->actingAs($user)->get(route('events.show', [$event, 'stock' => 'unredeemed']))
        ->assertOk()
        ->assertSee('待っている景品')
        ->assertDontSee('まだの景品')
        ->assertDontSee('渡した景品')
        ->assertSee('aria-current="true"', false);

    $prize = Prize::where('name', '待っている景品')->sole();
    $this->get(route('events.prizes.show', [$event, $prize, 'back' => 'unredeemed']))
        ->assertSee(e(route('events.show', [$event, 'stock' => 'unredeemed'])).'#prize-list', false);
});

test('the prize form asks to choose between a win and a miss', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $prize = Prize::factory()->for($event)->miss()->create();

    $this->actingAs($user);

    $this->get(route('events.prizes.create', $event))
        ->assertSee('data-prize-form', false)
        ->assertSee('name="is_miss" value="0" class="h-5 w-5 accent-ink" checked', false)
        ->assertSee('name="is_miss" value="1" class="h-5 w-5 accent-ink"  data-prize-kind', false);

    $this->get(route('events.prizes.edit', [$event, $prize]))
        ->assertSee('name="is_miss" value="1" class="h-5 w-5 accent-ink" checked', false);

    $this->post(route('events.prizes.store', $event), ['name' => '当たりの景品', 'is_miss' => '0'])->assertRedirect();
    expect($event->prizes()->where('name', '当たりの景品')->sole()->is_miss)->toBeFalse();
});

test('participants are shown as cards on phones with clear redemption counts', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create(['nickname' => 'ヒロキ']);
    Prize::factory()->count(2)->for($event)->takenBy($participant)->create();

    $this->actingAs($user)->get(route('events.participants', $event))
        ->assertOk()
        ->assertSee('data-participant-cards', false)
        ->assertSee('未引き換え 2個')
        ->assertSee('獲得景品を見る')
        ->assertSee('href="'.route('events.participants.show', [$event, $participant]).'" class="font-black', false);
});

test('a miss never keeps a prize name', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $this->actingAs($user);

    $this->post(route('events.prizes.store', $event), ['name' => '残念賞', 'is_miss' => '1'])->assertRedirect();
    $miss = $event->prizes()->sole();
    expect($miss->name)->toBe('ハズレ')->and($miss->is_miss)->toBeTrue();

    $this->get(route('events.prizes.edit', [$event, $miss]))
        ->assertSee('placeholder="ハズレ（景品名は入れません）"', false)
        ->assertSee('data-placeholder-miss="ハズレ（景品名は入れません）" disabled', false);
});

test('the participant pages lead back to the event and on to the next scan', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create();

    $this->actingAs($user);

    $this->get(route('events.participants', $event))
        ->assertSee('data-back-to-event', false)
        ->assertSee('イベントのページに戻る');

    $this->get(route('events.participants.show', [$event, $participant]))
        ->assertSee(route('redemption.scan', ['event' => $event->id]), false)
        ->assertSee('次の引き換えQRを読み取る')
        ->assertSee('イベントのページに戻る');
});

test('the snapshot button appears on the day and carries every prize', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['time_limit_minutes' => 30]);
    $participant = Participant::factory()->for($event)->create(['nickname' => '<b>たろう</b>']);
    Prize::factory()->for($event)->takenBy($participant)->create(['name' => '見つかった景品']);
    Prize::factory()->for($event)->create(['name' => 'まだの景品']);

    $this->actingAs($user);

    // 準備中は出さない
    $fresh = Event::factory()->for($user)->create();
    Prize::factory()->for($fresh)->create();
    $this->get(route('events.show', $fresh))->assertDontSee('data-snapshot-button', false);

    $event->startTimer();
    $response = $this->get(route('events.show', [$event, 'stock' => 'unredeemed']))
        ->assertSee('data-snapshot-button', false)
        ->assertSee('控えを保存')
        ->assertDontSee('<b>たろう</b>', false);

    // 絞り込んでいても、控えには全部の景品が入る
    preg_match('/<script type="application\/json" data-snapshot-data>(.*?)<\/script>/s', $response->getContent(), $m);
    $data = json_decode($m[1], true);
    expect(collect($data['prizes'])->pluck('name')->all())->toBe(['見つかった景品', 'まだの景品'])
        ->and($data['prizes'][0]['status'])->toBe('引き換え待ち')
        ->and($data['prizes'][0]['winner'])->toBe('<b>たろう</b>');
});

test('the snapshot asks the server for the latest state when it is pressed', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    $prize = Prize::factory()->for($event)->create(['name' => 'あとで見つかる景品']);

    $this->actingAs($user);
    $this->getJson(route('events.snapshot', $event))->assertOk()->assertJsonPath('prizes.0.status', '未獲得');

    // ページを開いたあとに見つかっても、押した時点の状態が返る
    $prize->forceFill(['participant_id' => $participant->id, 'acquired_at' => now()])->save();
    $this->getJson(route('events.snapshot', $event))
        ->assertOk()
        ->assertJsonPath('prizes.0.status', '引き換え待ち')
        ->assertJsonPath('prizes.0.winner', 'たろう')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->actingAs(User::factory()->create())->getJson(route('events.snapshot', $event))->assertForbidden();
});

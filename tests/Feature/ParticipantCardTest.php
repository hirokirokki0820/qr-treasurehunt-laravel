<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

function cardEvent(array $attributes = []): array
{
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create($attributes);

    return [$user, $event];
}

test('organizers make named or numbered participant cards and print them', function () {
    [$user, $event] = cardEvent();

    $this->actingAs($user)->get(route('events.show', $event))->assertSee(route('cards.index', $event));
    $this->get(route('cards.index', $event))->assertOk()->assertSee('参加者カード（スマホがない子用）');

    $this->post(route('cards.store', $event), ['names' => "たろう\n\n はなこ \nあおぐみ"])->assertRedirect();
    $this->post(route('cards.store', $event), ['count' => 2])->assertRedirect();

    $cards = $event->participants()->whereNotNull('card_token')->orderBy('id')->get();
    expect($cards->pluck('nickname')->all())->toBe(['たろう', 'はなこ', 'あおぐみ', 'カード4', 'カード5'])
        ->and($cards->every(fn (Participant $card) => strlen($card->card_token) === 40))->toBeTrue();

    $this->get(route('cards.print', $event))
        ->assertOk()
        ->assertSee('data-qr="'.route('cards.play', $cards->first()->card_token).'"', false)
        ->assertSee('お宝を見つけたら、受付でこのカードを見せてね');

    $this->get(route('events.participants', $event))->assertSee('カード');
});

test('making cards needs names or a count', function () {
    [$user, $event] = cardEvent();

    $this->actingAs($user)->post(route('cards.store', $event), ['names' => '  '])->assertSessionHasErrors('names');
    $this->post(route('cards.store', $event), ['count' => 101])->assertSessionHasErrors('count');

    expect($event->participants()->count())->toBe(0);
});

test('a card player wins a prize through the organizer phone and it is handed over at once', function () {
    [$user, $event] = cardEvent(['max_prizes_per_participant' => 1]);
    $event->createCards(['たろう']);
    $card = $event->participants()->sole();
    $prize = Prize::factory()->for($event)->create(['name' => 'お菓子セット']);
    $other = Prize::factory()->for($event)->create();

    $this->actingAs($user)->get(route('cards.play', $card->card_token))
        ->assertOk()
        ->assertSee('たろう の番')
        ->assertSee('data-rewrite-to="/host/card/'.$card->card_token.'/treasure/"', false);

    $this->get(route('cards.treasure', [$card->card_token, $prize->token]))->assertOk()->assertSee('開ける！');
    $this->post(route('cards.claim', [$card->card_token, $prize->token]))
        ->assertRedirect(route('cards.treasure', [$card->card_token, $prize->token]));

    $prize->refresh();
    expect($prize->participant_id)->toBe($card->id)
        ->and($prize->isRedeemed())->toBeTrue();

    $this->get(route('cards.treasure', [$card->card_token, $prize->token]))
        ->assertSee('お菓子セット')
        ->assertSee('その場で景品を渡してください');

    // 獲得上限は参加者カードの子にも効く
    $this->post(route('cards.claim', [$card->card_token, $other->token]));
    expect($other->fresh()->participant_id)->toBeNull();
});

test('a treasure from another event is not opened for a card player', function () {
    [$user, $event] = cardEvent();
    $event->createCards(['たろう']);
    $card = $event->participants()->sole();
    $foreign = Prize::factory()->create();

    $this->actingAs($user)->get(route('cards.treasure', [$card->card_token, $foreign->token]))
        ->assertRedirect(route('cards.play', $card->card_token));
    $this->post(route('cards.claim', [$card->card_token, $foreign->token]))->assertNotFound();

    expect($foreign->fresh()->participant_id)->toBeNull();
});

test('card pages are only for the organizer of that event', function () {
    [, $event] = cardEvent();
    $event->createCards(['たろう']);
    $card = $event->participants()->sole();
    $prize = Prize::factory()->for($event)->create();

    // 子どもが自分のスマホでカードを読んでも、主催者のログイン画面になるだけ
    $this->get(route('cards.play', $card->card_token))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('cards.index', $event))->assertForbidden();
    $this->post(route('cards.store', $event), ['count' => 1])->assertForbidden();
    $this->get(route('cards.play', $card->card_token))->assertForbidden();
    $this->post(route('cards.claim', [$card->card_token, $prize->token]))->assertForbidden();

    expect($prize->fresh()->participant_id)->toBeNull();
});

test('cards count toward the trial mode limit', function () {
    [$user, $event] = cardEvent(['trial_mode' => true]);
    Participant::factory()->for($event)->create();

    $this->actingAs($user)->post(route('cards.store', $event), ['count' => 5])->assertSessionHas('status');

    expect($event->participants()->count())->toBe(Event::TRIAL_PARTICIPANT_LIMIT);
});

test('the scanners accept card and treasure codes in card mode', function () {
    [$user] = cardEvent();

    $this->actingAs($user)->get(route('cards.scan'))->assertOk()->assertSee('data-accept-path="/host/card/"', false);
});

test('cards print 3 by 3 by default and 2 by 2 when large is chosen', function () {
    [$user, $event] = cardEvent();
    $event->createCards(['たろう']);

    $this->actingAs($user)->get(route('cards.print', $event))
        ->assertOk()
        ->assertSee('print:grid-cols-3 print:grid-rows-3')
        ->assertSee('2×2枚')
        ->assertSee('3×3枚');

    $this->get(route('cards.print', [$event, 'size' => 'large']))
        ->assertSee('print:grid-cols-2 print:grid-rows-2');
});

test('the camera pages lead back to the event they were opened from', function () {
    [$user, $event] = cardEvent();

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertSee(route('redemption.scan', ['event' => $event->id]), false);
    $this->get(route('cards.index', $event))
        ->assertSee(route('cards.scan', ['event' => $event->id]), false);

    $this->get(route('redemption.scan', ['event' => $event->id]))
        ->assertSee('イベントのページに戻る')
        ->assertSee(route('events.show', $event), false);
    $this->get(route('cards.scan', ['event' => $event->id]))
        ->assertSee('参加者カードのページに戻る')
        ->assertSee(route('cards.index', $event), false);

    // ほかの主催者のイベントは、戻り先として出さない
    $foreign = Event::factory()->create(['name' => 'よその会']);
    $this->get(route('redemption.scan', ['event' => $foreign->id]))->assertOk()->assertDontSee('よその会');
    $this->get(route('cards.scan', ['event' => $foreign->id]))->assertOk()->assertDontSee('よその会');
});

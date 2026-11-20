<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('organizers can switch to paper during the event and back again', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->for($event)->create();

    $this->actingAs($owner)->get(route('events.show', $event))
        ->assertSee('data-paper-switch', false)
        ->assertSee('全員がつながらなくなったとき（緊急時）');

    $this->post(route('events.paper.start', $event))->assertRedirect(route('events.show', $event));
    expect($event->fresh()->isPaperMode())->toBeTrue();

    $this->get(route('events.show', $event))
        ->assertSee('data-paper-mode', false)
        ->assertSee('参加者のアプリでは、宝箱を開けられないようにしています')
        ->assertDontSee('data-paper-switch', false);

    $this->delete(route('events.paper.end', $event))->assertRedirect(route('events.show', $event));
    expect($event->fresh()->isPaperMode())->toBeFalse();
});

test('only the organizer can switch an event to paper', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('events.paper.start', $event))->assertForbidden();
    expect($event->fresh()->isPaperMode())->toBeFalse();
});

test('participants cannot open treasures in the app after switching to paper', function () {
    $prize = Prize::factory()->create(['name' => 'お菓子']);
    $prize->event->forceFill(['paper_mode_at' => now()])->save();
    $participant = Participant::factory()->for($prize->event)->create();

    $this->actingAs($participant, 'participant')->get(route('treasure.show', $prize))
        ->assertSee('ここからは紙で宝探し！')
        ->assertDontSee('開ける！');

    $this->followingRedirects()->post(route('treasure.claim', $prize))->assertSee('ここからは紙で宝探し！');
    expect($prize->fresh()->participant_id)->toBeNull();

    $this->get(route('play.index'))->assertSee('data-paper-mode', false)->assertDontSee('QRコードを読み取る');
    $this->getJson(route('play.status'))->assertJson(['paper' => true]);
});

test('organizers cannot open treasures with participant cards after switching to paper', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create(['paper_mode_at' => now()]);
    $prize = Prize::factory()->for($event)->create();
    $card = Participant::factory()->for($event)->create(['card_token' => 'card-token-123']);

    $this->actingAs($owner)->post(route('cards.claim', [$card->card_token, $prize->token]))->assertRedirect();
    expect($prize->fresh()->participant_id)->toBeNull();
    $this->get(route('cards.treasure', [$card->card_token, $prize->token]))->assertSee('紙に切り替えています');
});

test('cleaning up the event ends paper mode', function () {
    $event = Event::factory()->create(['paper_mode_at' => now()]);
    $event->end();

    expect($event->fresh()->isPaperMode())->toBeFalse();
});

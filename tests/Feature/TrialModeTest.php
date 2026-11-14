<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('an organizer can start trial mode on an event nobody has joined yet', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertSee('本番の前に試してみる')
        ->assertSee(route('events.trial.start', $event));

    $this->post(route('events.trial.start', $event))->assertRedirect();

    expect($event->fresh()->trial_mode)->toBeTrue();
    $this->get(route('events.show', $event))->assertSee('data-trial-mode', false)->assertSee('お試しを終えて本番にする');
});

test('trial mode cannot start once real participants have joined', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Participant::factory()->for($event)->create();

    $this->actingAs($user)->get(route('events.show', $event))->assertDontSee('本番の前に試してみる');
    $this->post(route('events.trial.start', $event))->assertSessionHasErrors('trial');

    expect($event->fresh()->trial_mode)->toBeFalse();
});

test('only three people can join during trial mode', function () {
    $event = Event::factory()->create(['trial_mode' => true]);
    Participant::factory()->count(Event::TRIAL_PARTICIPANT_LIMIT)->for($event)->create();

    $this->get(route('join', $event))->assertOk()->assertSee('お試しモードの参加人数（3人）に達しています');
    $this->post(route('join.store', $event), ['nickname' => 'よにんめ'])->assertSessionHasErrors('nickname');

    expect($event->participants()->count())->toBe(Event::TRIAL_PARTICIPANT_LIMIT);
    $this->assertGuest('participant');
});

test('ending the trial clears trial players, wins and the timer but keeps prizes', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['trial_mode' => true, 'time_limit_minutes' => 10]);
    $player = Participant::factory()->for($event)->create();
    $prize = Prize::factory()->takenBy($player)->create();
    $event->startTimer();
    $event->markPrizeListPrinted();

    $this->actingAs($user)->post(route('events.trial.end', $event))->assertRedirect();

    $event->refresh();
    expect($event->trial_mode)->toBeFalse()
        ->and($event->participants()->count())->toBe(0)
        ->and($event->started_at)->toBeNull()
        ->and($event->prize_list_printed_at)->not->toBeNull()
        ->and($prize->fresh()->participant_id)->toBeNull()
        ->and($event->prizes()->count())->toBe(1);

    $this->post(route('join.store', $event), ['nickname' => '本番の人'])->assertRedirect(route('play.index'));
});

test('other organizers cannot switch trial mode', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('events.trial.start', $event))->assertForbidden();
    $event->update([]);
    $event->forceFill(['trial_mode' => true])->save();
    $this->post(route('events.trial.end', $event))->assertForbidden();

    expect($event->fresh()->trial_mode)->toBeTrue();
});

test('participants see that the event is in trial mode', function () {
    $event = Event::factory()->create(['trial_mode' => true]);

    $this->post(route('join.store', $event), ['nickname' => 'たろう']);
    $this->get(route('play.index'))->assertSee('お試しモード');
});

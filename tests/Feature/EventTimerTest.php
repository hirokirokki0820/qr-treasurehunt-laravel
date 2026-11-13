<?php

use App\Enums\ClaimResult;
use App\Enums\TimerState;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

function timedEvent(?int $minutes = 30): array
{
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create(['time_limit_minutes' => $minutes]);
    $participant = Participant::factory()->for($event)->create();
    $prize = Prize::factory()->for($event)->create(['name' => 'お菓子']);

    return [$organizer, $event, $participant, $prize];
}

test('organizers set a time limit when creating an event', function () {
    $organizer = User::factory()->create();

    $this->actingAs($organizer)
        ->post(route('events.store'), ['name' => '秋の宝探し', 'time_limit_minutes' => 30]);

    expect($organizer->events()->sole())
        ->time_limit_minutes->toBe(30)
        ->timerState()->toBe(TimerState::Waiting);
});

test('events without a time limit behave as before', function () {
    [, $event, $participant, $prize] = timedEvent(null);

    expect($event->timerState())->toBe(TimerState::None)
        ->and($prize->claimFor($participant))->toBe(ClaimResult::Won);
});

test('treasures cannot be opened before the start or after the time is up', function () {
    [$organizer, $event, $participant, $prize] = timedEvent();

    expect($prize->claimFor($participant))->toBe(ClaimResult::NotStarted);

    $this->actingAs($participant, 'participant')
        ->get(route('treasure.show', $prize))
        ->assertSee('まだスタートしていません')
        ->assertDontSee('開ける！');

    $this->travelTo(now()->setTime(14, 0));
    $this->actingAs($organizer, 'web')->post(route('events.timer.start', $event))->assertRedirect();

    expect($event->fresh())
        ->timerState()->toBe(TimerState::Running)
        ->ends_at->format('H:i')->toBe('14:30');

    $this->travelTo(now()->setTime(14, 31));

    expect($prize->fresh()->claimFor($participant->fresh()))->toBe(ClaimResult::TimeUp)
        ->and($prize->fresh()->participant_id)->toBeNull();
});

test('prizes can be won while the hunt is running', function () {
    [, $event, $participant, $prize] = timedEvent();
    $event->startTimer();

    expect($prize->claimFor($participant->fresh()))->toBe(ClaimResult::Won);
});

test('organizers can extend and stop a running hunt, then start it again', function () {
    [$organizer, $event] = timedEvent();
    $this->travelTo(now()->setTime(14, 0));
    $event->startTimer();

    $this->actingAs($organizer)->post(route('events.timer.extend', $event));
    expect($event->fresh()->ends_at->format('H:i'))->toBe('14:35');

    $this->post(route('events.timer.stop', $event));
    expect($event->fresh()->timerState())->toBe(TimerState::Ended);

    $this->post(route('events.timer.start', $event));
    expect($event->fresh()->timerState())->toBe(TimerState::Running);

    $this->get(route('events.show', $event))->assertSee('宝探し中')->assertSee('今すぐ締め切る');
});

test('other organizers cannot control the timer', function () {
    [, $event] = timedEvent();

    $this->actingAs(User::factory()->create())
        ->post(route('events.timer.start', $event))
        ->assertForbidden();

    expect($event->fresh()->timerState())->toBe(TimerState::Waiting);
});

test('participants see the waiting, running and ended states and can poll the status', function () {
    [, $event, $participant] = timedEvent();

    $this->actingAs($participant, 'participant')
        ->get(route('play.index'))
        ->assertSee('まもなく宝探しスタート！')
        ->assertSee('data-status-url="'.route('play.status').'"', false)
        ->assertDontSee(route('play.scan').'" class="btn-primary mt-4', false);

    $this->getJson(route('play.status'))->assertJson(['state' => 'waiting', 'remaining' => null]);

    $event->startTimer();
    $participant->unsetRelation('event');

    $this->get(route('play.index'))->assertSee('残り時間');
    $status = $this->getJson(route('play.status'))->assertJson(['state' => 'running'])->json();
    expect($status['remaining'])->toBeGreaterThan(1790)->toBeLessThanOrEqual(1800);

    $event->stopTimer();
    $participant->unsetRelation('event');

    $this->get(route('play.index'))->assertSee('宝探しは終了しました！');
    $this->get(route('play.scan'))->assertSee('宝探しは終了しました！')->assertDontSee('id="qr-scanner"', false);
});

test('ending the event clears the timer', function () {
    [, $event] = timedEvent();
    $event->startTimer();

    $event->end();

    expect($event->fresh()->timerState())->toBe(TimerState::Waiting);
});

test('the qr scanner page shows a compact countdown while the hunt is running', function () {
    [, $event, $participant] = timedEvent();
    $event->startTimer();

    $this->actingAs($participant, 'participant')
        ->get(route('play.scan'))
        ->assertSee('id="qr-scanner"', false)
        ->assertSee('data-timer-clock', false)
        ->assertSee('data-status-url="'.route('play.status').'"', false);

    $this->getJson(route('play.status'))->assertJsonStructure(['state', 'remaining', 'total', 'ends', 'version']);
});

test('the qr scanner page has no countdown when the event has no time limit', function () {
    [, , $participant] = timedEvent(null);

    $this->actingAs($participant, 'participant')
        ->get(route('play.scan'))
        ->assertSee('id="qr-scanner"', false)
        ->assertDontSee('data-timer-clock', false);
});

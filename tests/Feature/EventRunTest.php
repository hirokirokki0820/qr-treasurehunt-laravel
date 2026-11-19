<?php

use App\Models\Event;
use App\Models\EventRun;
use App\Models\Participant;
use App\Models\User;

test('a live run is recorded when the fourth participant joins and later joins update the peak', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    Participant::factory()->count(3)->for($event)->create();
    expect(EventRun::query()->count())->toBe(0);

    $this->post(route('join.store', $event), ['nickname' => 'よにんめ'])->assertRedirect();
    expect(EventRun::query()->count())->toBe(1);

    $this->actingAs($owner)->post(route('cards.store', $event), ['count' => 3]);

    $run = EventRun::query()->sole();
    expect($run->user_id)->toBe($owner->id)
        ->and($run->event_id)->toBe($event->id)
        ->and($run->peak_participants)->toBe(7);
});

test('trial mode is not counted as a run', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create(['trial_mode' => true]);

    Participant::factory()->count(3)->for($event)->create();
    expect(EventRun::query()->count())->toBe(0);

    $event->endTrial();
    Participant::factory()->count(4)->for($event->fresh())->create();
    expect(EventRun::query()->count())->toBe(1);
});

test('joining again after cleaning up the event records the next run and keeps the old one', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    Participant::factory()->count(4)->for($event)->create();
    $event->end();
    Participant::factory()->count(4)->for($event)->create();

    expect($owner->eventRuns()->count())->toBe(2);

    $event->forceDelete();
    expect($owner->eventRuns()->count())->toBe(2)
        ->and($owner->eventRuns()->whereNull('event_id')->count())->toBe(2);
});

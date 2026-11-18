<?php

use App\Models\Event;
use App\Models\EventRun;
use App\Models\Participant;
use App\Models\User;

test('the first participant of a live event records one run and later joins update the peak', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    $this->post(route('join.store', $event), ['nickname' => 'ひとりめ'])->assertRedirect();
    expect(EventRun::query()->count())->toBe(1);

    Participant::factory()->for($event)->create();
    $this->actingAs($owner)->post(route('cards.store', $event), ['count' => 3]);

    $run = EventRun::query()->sole();
    expect($run->user_id)->toBe($owner->id)
        ->and($run->event_id)->toBe($event->id)
        ->and($run->peak_participants)->toBe(5);
});

test('trial mode is not counted as a run', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create(['trial_mode' => true]);

    $this->post(route('join.store', $event), ['nickname' => 'おためし'])->assertRedirect();
    expect(EventRun::query()->count())->toBe(0);

    $event->endTrial();
    Participant::factory()->for($event->fresh())->create();
    expect(EventRun::query()->count())->toBe(1);
});

test('joining again after cleaning up the event records the next run and keeps the old one', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    Participant::factory()->for($event)->create();
    $event->end();
    Participant::factory()->for($event)->create();

    expect($owner->eventRuns()->count())->toBe(2);

    $event->forceDelete();
    expect($owner->eventRuns()->count())->toBe(2)
        ->and($owner->eventRuns()->whereNull('event_id')->count())->toBe(2);
});

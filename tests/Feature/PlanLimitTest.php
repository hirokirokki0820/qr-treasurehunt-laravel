<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

test('organizers who are not monitors can let only three people join', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->count(User::FREE_PARTICIPANT_LIMIT)->for($event)->create();

    $this->get(route('join', $event))->assertOk()->assertSee('参加できる人数（3人）に達しています');
    $this->post(route('join.store', $event), ['nickname' => 'よにんめ'])->assertSessionHasErrors('nickname');

    expect($event->participants()->count())->toBe(User::FREE_PARTICIPANT_LIMIT);
    $this->assertGuest('participant');
});

test('the organizer page tells free organizers how to become a monitor', function () {
    config(['services.kobapp.url' => 'https://kobapp.example.test']);
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    $this->actingAs($owner)->get(route('events.show', $event))
        ->assertSee('data-plan-limit', false)
        ->assertSee('https://kobapp.example.test/monitor')
        ->assertSee('ログアウトして「kobapp でログイン」で入り直してください');

    $monitor = User::factory()->monitor()->create();
    $this->actingAs($monitor)->get(route('events.show', Event::factory()->for($monitor)->create()))
        ->assertDontSee('data-plan-limit', false);
});

test('monitors can let up to one hundred people join', function () {
    $owner = User::factory()->monitor()->create();
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->count(User::MONITOR_PARTICIPANT_LIMIT - 1)->for($event)->create();

    $this->post(route('join.store', $event), ['nickname' => 'ひゃくにんめ'])->assertRedirect();
    expect($event->participants()->count())->toBe(User::MONITOR_PARTICIPANT_LIMIT);
    expect($event->fresh()->isFull())->toBeTrue();
});

test('admins have no limit except in trial mode', function () {
    $owner = User::factory()->create(['plan' => 'admin']);
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->count(User::MONITOR_PARTICIPANT_LIMIT)->for($event)->create();

    expect($event->participantLimit())->toBeNull()
        ->and($event->isFull())->toBeFalse();

    $trial = Event::factory()->for($owner)->create(['trial_mode' => true]);
    expect($trial->participantLimit())->toBe(Event::TRIAL_PARTICIPANT_LIMIT);
});

test('cards are made only up to the limit of free organizers', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    $this->actingAs($owner)->post(route('cards.store', $event), ['count' => 5])
        ->assertSessionHas('status', fn (string $status) => str_contains($status, '3枚作りました') && str_contains($status, '参加できるのは3人まで'));

    expect($event->participants()->count())->toBe(User::FREE_PARTICIPANT_LIMIT);
});

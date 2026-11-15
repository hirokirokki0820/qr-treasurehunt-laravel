<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Carbon;

test('an event can be created with or without a schedule', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('events.store'), [
        'name' => '秋の運動会',
        'scheduled_date' => '2026-11-29',
        'scheduled_time' => '10:00',
        'scheduled_minutes' => '120',
    ])->assertRedirect();

    $event = Event::where('name', '秋の運動会')->sole();
    expect($event->requires_schedule)->toBeTrue()
        ->and($event->scheduled_start_at->format('Y-m-d H:i'))->toBe('2026-11-29 10:00')
        ->and($event->scheduleLabel())->toBe('11月29日（日）10:00〜12:00');

    // 空欄でも作れる（開催時間の選択は、日付がないときは使わない）
    $this->post(route('events.store'), ['name' => 'あとで決める', 'scheduled_minutes' => '120'])->assertRedirect();
    $later = Event::where('name', 'あとで決める')->sole();
    expect($later->hasSchedule())->toBeFalse()->and($later->needsScheduleToStart())->toBeTrue();

    // 日付だけ・時刻だけは受け付けない
    $this->post(route('events.store'), ['name' => '途中', 'scheduled_date' => '2026-11-29'])->assertSessionHasErrors('scheduled_time');
    $this->post(route('events.store'), ['name' => '途中', 'scheduled_time' => '10:00'])->assertSessionHasErrors('scheduled_date');
});

test('without a schedule the real start and joining are blocked but trial mode works', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->withoutSchedule()->create(['time_limit_minutes' => 30]);
    $this->actingAs($user);

    $this->get(route('events.show', $event))
        ->assertSee('data-schedule-notice', false)
        ->assertSee('data-schedule-missing', false)
        ->assertSee('data-start-blocked', false);

    $this->post(route('events.timer.start', $event))->assertSessionHasErrors('schedule');
    expect($event->fresh()->started_at)->toBeNull();

    auth('web')->logout();
    $this->get(route('join', $event))->assertSee('data-join-not-open', false);
    $this->post(route('join.store', $event), ['nickname' => 'たろう'])->assertSessionHasErrors('nickname');
    expect($event->participants()->count())->toBe(0);

    // お試しモードなら参加できる
    $event->startTrial();
    $this->post(route('join.store', $event), ['nickname' => 'はなこ'])->assertRedirect();
    expect($event->participants()->count())->toBe(1);
});

test('events made before this feature keep working without a schedule', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->withoutSchedule()->create(['time_limit_minutes' => 30]);
    $event->forceFill(['requires_schedule' => false])->save();

    expect($event->fresh()->needsScheduleToStart())->toBeFalse();
    $this->actingAs($user)->post(route('events.timer.start', $event))->assertRedirect();
    expect($event->fresh()->started_at)->not->toBeNull();
});

test('a running event cannot lose its schedule but can be moved', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create(['name' => '本番中']);
    Participant::factory()->for($event)->create();
    $this->actingAs($user);

    $this->put(route('events.update', $event), ['name' => '本番中', 'scheduled_minutes' => '120'])->assertSessionHasErrors('scheduled_date');
    expect($event->fresh()->hasSchedule())->toBeTrue();

    $this->put(route('events.update', $event), ['name' => '本番中', 'scheduled_date' => '2026-12-06', 'scheduled_time' => '13:30', 'scheduled_minutes' => '90'])->assertRedirect();
    expect($event->fresh()->scheduleLabel())->toBe('12月6日（日）13:30〜15:00');
});

function signedScheduleRequest(string $from, string $to, ?string $secret = 'test-secret', ?int $at = null): array
{
    $timestamp = (string) ($at ?? now()->getTimestamp());

    return [
        route('api.kobapp.schedule', ['from' => $from, 'to' => $to]),
        ['X-Kobapp-Timestamp' => $timestamp, 'X-Kobapp-Signature' => hash_hmac('sha256', $timestamp."\n".$from."\n".$to, (string) $secret)],
    ];
}

test('kobapp can read the schedule only with a valid signature', function () {
    config(['services.kobapp.client_secret' => 'test-secret']);
    $owner = User::factory()->create(['kobapp_user_id' => '42', 'email' => 'host@example.com']);
    Event::factory()->for($owner)->create(['name' => '11月の宝探し', 'scheduled_start_at' => Carbon::parse('2026-11-21 10:00'), 'scheduled_minutes' => 120]);
    Event::factory()->for($owner)->create(['name' => '12月の宝探し', 'scheduled_start_at' => Carbon::parse('2026-12-21 10:00'), 'scheduled_minutes' => 60]);
    Event::factory()->for($owner)->withoutSchedule()->create(['name' => '未定の宝探し']);

    [$url, $headers] = signedScheduleRequest('2026-11-01', '2026-11-30');
    $response = $this->getJson($url, $headers)->assertOk();

    expect($response->json('events'))->toHaveCount(1)
        ->and($response->json('events.0.name'))->toBe('11月の宝探し')
        ->and($response->json('events.0.owner_sub'))->toBe('42')
        ->and($response->json('events.0.scheduled_minutes'))->toBe(120)
        ->and($response->json('unscheduled.0.name'))->toBe('未定の宝探し');
    expect($response->getContent())->not->toContain('host@example.com');

    // 署名がちがう・古い・合言葉がない
    [$url, $headers] = signedScheduleRequest('2026-11-01', '2026-11-30', 'wrong-secret');
    $this->getJson($url, $headers)->assertUnauthorized();
    [$url, $headers] = signedScheduleRequest('2026-11-01', '2026-11-30', at: now()->subMinutes(10)->getTimestamp());
    $this->getJson($url, $headers)->assertUnauthorized();
    $this->getJson(route('api.kobapp.schedule', ['from' => '2026-11-01', 'to' => '2026-11-30']))->assertUnauthorized();

    config(['services.kobapp.client_secret' => null]);
    [$url, $headers] = signedScheduleRequest('2026-11-01', '2026-11-30', '');
    $this->getJson($url, $headers)->assertUnauthorized();
});

test('the schedule range is limited', function () {
    config(['services.kobapp.client_secret' => 'test-secret']);

    [$url, $headers] = signedScheduleRequest('2026-01-01', '2026-12-31');
    $this->getJson($url, $headers)->assertStatus(422);
    [$url, $headers] = signedScheduleRequest('not-a-date', '2026-12-31');
    $this->getJson($url, $headers)->assertStatus(422);
});

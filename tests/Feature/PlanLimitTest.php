<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('organizers who are not monitors can let up to forty people join', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->count(User::FREE_PARTICIPANT_LIMIT)->for($event)->create();

    $this->get(route('join', $event))->assertOk()->assertSee('このイベントは参加人数の上限に達しました。くわしくは主催者にお問い合わせください。');
    $this->post(route('join.store', $event), ['nickname' => 'よんじゅういちにんめ'])->assertSessionHasErrors('nickname');

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
    $owner = User::factory()->create();
    expect($owner->fresh()->plan)->toBe('admin');
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

    Participant::factory()->count(User::FREE_PARTICIPANT_LIMIT - 2)->for($event)->create();

    $this->actingAs($owner)->post(route('cards.store', $event), ['count' => 5])
        ->assertSessionHas('status', fn (string $status) => str_contains($status, '2枚作りました') && str_contains($status, '参加できるのは40人まで'));

    expect($event->participants()->count())->toBe(User::FREE_PARTICIPANT_LIMIT);
});

test('free organizers can register up to one hundred treasure qr codes including misses', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();
    Prize::factory()->count(User::FREE_PRIZE_LIMIT - 2)->for($event)->create();
    Prize::factory()->for($event)->create(['is_miss' => true]);

    $this->actingAs($owner)->get(route('events.show', $event))
        ->assertSee('お宝QR '.(User::FREE_PRIZE_LIMIT - 1).'／'.User::FREE_PRIZE_LIMIT.'枚');

    $this->post(route('events.prizes.store', $event), ['name' => 'ひゃくこめ'])->assertRedirect();
    expect($event->prizes()->count())->toBe(User::FREE_PRIZE_LIMIT);

    $this->post(route('events.prizes.store', $event), ['name' => 'ひゃくいっこめ'])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('prize');
    $this->get(route('events.prizes.create', $event))->assertRedirect(route('events.show', $event));
    expect($event->prizes()->count())->toBe(User::FREE_PRIZE_LIMIT);

    $this->get(route('events.show', $event))->assertSee('data-prize-limit-reached', false);
});

test('monitors can register up to three hundred treasure qr codes and admins have no limit', function () {
    expect(User::factory()->monitor()->create()->prizeLimit())->toBe(User::MONITOR_PRIZE_LIMIT)
        ->and(User::factory()->create()->prizeLimit())->toBeNull();
});

test('copying prizes from a past event stops at the limit', function () {
    $owner = User::factory()->free()->create();
    $source = Event::factory()->for($owner)->create();
    Prize::factory()->count(User::FREE_PRIZE_LIMIT + 5)->for($source)->create();

    $this->actingAs($owner)->post(route('events.store'), ['name' => '引き継ぎ', 'copy_from' => $source->id, 'copy_prizes' => '1'])->assertRedirect();

    $copy = $owner->events()->where('name', '引き継ぎ')->firstOrFail();
    expect($copy->prizes()->count())->toBe(User::FREE_PRIZE_LIMIT)
        ->and($copy->prizes()->max('number'))->toBe($source->prizes()->orderBy('number')->skip(User::FREE_PRIZE_LIMIT - 1)->value('number'));
});

test('an account can have up to fifty events', function () {
    $owner = User::factory()->free()->create();
    Event::factory()->count(User::EVENT_LIMIT)->for($owner)->create();

    $this->actingAs($owner)->get(route('events.create'))->assertRedirect(route('events.index'));
    $this->post(route('events.store'), ['name' => 'ごじゅういっけんめ'])
        ->assertRedirect(route('events.index'))
        ->assertSessionHasErrors('event');
    expect($owner->events()->count())->toBe(User::EVENT_LIMIT);

    $owner->events()->first()->delete();
    $this->get(route('events.create'))->assertOk();

    $admin = User::factory()->create();
    Event::factory()->count(User::EVENT_LIMIT)->for($admin)->create();
    $this->actingAs($admin)->get(route('events.create'))->assertOk();
});

test('free organizers are told that live runs are unlimited until paid plans start', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();

    $this->actingAs($owner)->get(route('events.show', $event))
        ->assertSee('本番は何度でも無料で使えます')
        ->assertSee('有料プランを始めるまでの期間限定です（始めたあとは年5回になる予定）');
});

test('the create page warns about the participant limit for free organizers but not for admins', function () {
    $this->actingAs(User::factory()->free()->create())->get(route('events.create'))
        ->assertSee('data-participant-limit-notice', false)
        ->assertSee('参加人数が上限（40人）に達すると、それ以上は参加できません')
        ->assertSee('別の端末から参加すると1人として数えます');

    $this->actingAs(User::factory()->create())->get(route('events.create'))
        ->assertDontSee('data-participant-limit-notice', false);
});

test('organizers are told when five or fewer places remain', function () {
    $owner = User::factory()->free()->create();
    $event = Event::factory()->for($owner)->create();
    Participant::factory()->count(User::FREE_PARTICIPANT_LIMIT - 6)->for($event)->create();

    $this->actingAs($owner)->get(route('events.show', $event))->assertDontSee('data-participant-room', false);

    Participant::factory()->for($event)->create();
    $this->get(route('events.show', $event))->assertSee('残り5人です（上限40人）');

    Participant::factory()->count(5)->for($event)->create();
    $this->get(route('events.show', $event))->assertSee('上限（40人）に達しました');
});

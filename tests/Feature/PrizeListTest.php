<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

test('the event page asks the organizer to print the prize list until it is opened', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->for($event)->create();

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-prize-list-reminder', false)
        ->assertSee('当日の備えに、景品リストを印刷しておきましょう')
        ->assertDontSee('景品リストを印刷（緊急時に紙で続ける用）');

    $this->get(route('events.prize-list', $event))->assertOk();

    expect($event->fresh()->prize_list_printed_at)->not->toBeNull();
    $this->get(route('events.show', $event))
        ->assertDontSee('data-prize-list-reminder', false)
        ->assertSee('景品リストを印刷（緊急時に紙で続ける用）');
});

test('an event without prizes does not ask for the prize list yet', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertOk()
        ->assertDontSee('data-prize-list-reminder', false);
});

test('adding a prize after printing asks to print the list again', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->for($event)->create();

    $this->actingAs($user)->get(route('events.prize-list', $event));
    $this->travel(1)->minutes();
    Prize::factory()->for($event)->create();

    $this->get(route('events.show', $event))
        ->assertSee('景品を足したので、景品リストを印刷し直しましょう');
});

test('ending the event asks for a fresh prize list next time', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    Prize::factory()->for($event)->create();

    $this->actingAs($user)->get(route('events.prize-list', $event));
    $this->post(route('events.end', $event));

    expect($event->fresh()->prize_list_printed_at)->toBeNull();
});

test('the prize list shows numbers, names, misses and who already got each prize', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();
    $participant = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    Prize::factory()->takenBy($participant)->create(['name' => 'お菓子セット', 'number' => 1]);
    Prize::factory()->for($event)->create(['name' => '残念賞', 'is_miss' => true, 'number' => 2]);

    $this->actingAs($user)->get(route('events.prize-list', $event))
        ->assertOk()
        ->assertSeeInOrder(['お宝No', '景品名', '渡した人'])
        ->assertSeeInOrder(['お菓子セット', '当たり', 'たろう', '残念賞', 'ハズレ'])
        ->assertSee('参加者には見せない');
});

test('another organizer cannot open the prize list', function () {
    $event = Event::factory()->create();
    Prize::factory()->for($event)->create(['name' => '秘密の景品']);

    $this->actingAs(User::factory()->create())
        ->get(route('events.prize-list', $event))
        ->assertForbidden();

    expect($event->fresh()->prize_list_printed_at)->toBeNull();
});

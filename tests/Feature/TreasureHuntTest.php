<?php

use App\Enums\ClaimResult;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('guests join an event with a nickname and stay logged in', function () {
    $event = Event::factory()->create();

    $this->get(route('join', $event))->assertOk()->assertSee($event->name);

    $this->post(route('join.store', $event), ['nickname' => 'たろう'])
        ->assertRedirect(route('play.index'));

    $participant = $event->participants()->sole();
    expect($participant->nickname)->toBe('たろう');
    $this->assertAuthenticatedAs($participant, 'participant');

    $this->get(route('join', $event))->assertRedirect(route('play.index'));
});

test('an unknown join token is not found', function () {
    $this->get('/join/not-a-real-token')->assertNotFound();
});

test('scanning a treasure before joining asks the visitor to join first', function () {
    $prize = Prize::factory()->create();

    $this->get(route('treasure.show', $prize))
        ->assertOk()
        ->assertViewIs('treasure.need-join');

    $this->post(route('treasure.claim', $prize))->assertRedirect(route('participant.required'));
    expect($prize->fresh()->participant_id)->toBeNull();
});

test('viewing a treasure does not claim it until it is opened', function () {
    $prize = Prize::factory()->create();
    $participant = Participant::factory()->for($prize->event)->create();

    $this->actingAs($participant, 'participant')
        ->get(route('treasure.show', $prize))
        ->assertOk()
        ->assertSee('開ける！');

    expect($prize->fresh()->participant_id)->toBeNull();
});

test('the first participant to open a treasure wins it', function () {
    $prize = Prize::factory()->create(['name' => 'お菓子']);
    $first = Participant::factory()->for($prize->event)->create();
    $second = Participant::factory()->for($prize->event)->create();

    $this->actingAs($first, 'participant')
        ->post(route('treasure.claim', $prize))
        ->assertRedirect(route('treasure.show', $prize));
    $this->get(route('treasure.show', $prize))->assertSee('おめでとう');
    $this->get(route('treasure.show', $prize))->assertSee('このお宝はもうあなたのものです');

    expect($prize->fresh())
        ->participant_id->toBe($first->id)
        ->acquired_at->not->toBeNull();

    $this->actingAs($second, 'participant')
        ->followingRedirects()
        ->post(route('treasure.claim', $prize))
        ->assertSee('ほかの人がすでに見つけていました');

    expect($prize->fresh()->participant_id)->toBe($first->id);
});

test('the winner sees the prize name right after opening', function () {
    $prize = Prize::factory()->create(['name' => '図書カード']);
    $participant = Participant::factory()->for($prize->event)->create();

    $this->actingAs($participant, 'participant')
        ->followingRedirects()
        ->post(route('treasure.claim', $prize))
        ->assertSee('おめでとう')
        ->assertSee('図書カード');
});

test('a miss can be opened by everyone and never runs out', function () {
    $prize = Prize::factory()->miss()->create();
    $participants = Participant::factory()->count(2)->for($prize->event)->create();

    foreach ($participants as $participant) {
        $this->actingAs($participant, 'participant')
            ->followingRedirects()
            ->post(route('treasure.claim', $prize))
            ->assertSee('ハズレ！');
    }

    expect($prize->fresh()->participant_id)->toBeNull();
});

test('participants who scan a treasure from another organizer see a notice instead of opening it', function () {
    $prize = Prize::factory()->create();
    $outsider = Participant::factory()->create();

    $this->actingAs($outsider, 'participant');

    $this->get(route('treasure.show', $prize))
        ->assertOk()
        ->assertViewIs('treasure.other-event')
        ->assertSee('別の主催者のイベント')
        ->assertSee($outsider->event->name)
        ->assertDontSee($prize->name)
        ->assertDontSee('開ける！');

    $this->post(route('treasure.claim', $prize))->assertRedirect(route('treasure.show', $prize));
    expect($prize->fresh()->participant_id)->toBeNull();
});

test('participants who scan a treasure from another event of the same organizer are told it is a different event', function () {
    $organizer = User::factory()->create();
    $joinedEvent = Event::factory()->for($organizer)->create();
    $otherEvent = Event::factory()->for($organizer)->create();
    $prize = Prize::factory()->for($otherEvent)->create();
    $participant = Participant::factory()->for($joinedEvent)->create();

    $this->actingAs($participant, 'participant')
        ->get(route('treasure.show', $prize))
        ->assertViewIs('treasure.other-event')
        ->assertSee('別のイベント')
        ->assertDontSee('別の主催者')
        ->assertSee('次のQRコードを読み取る');
});

test('the participant page lists only their own prizes', function () {
    $event = Event::factory()->create();
    $participant = Participant::factory()->for($event)->create();
    $rival = Participant::factory()->for($event)->create();
    Prize::factory()->for($event)->takenBy($participant)->create(['name' => 'わたしの景品']);
    Prize::factory()->for($event)->takenBy($rival)->create(['name' => 'ライバルの景品']);

    $this->actingAs($participant, 'participant')
        ->get(route('play.index'))
        ->assertOk()
        ->assertSee('わたしの景品')
        ->assertDontSee('ライバルの景品');
});

test('quitting releases the participants prizes back into stock', function () {
    $event = Event::factory()->create();
    $participant = Participant::factory()->for($event)->create();
    $prize = Prize::factory()->for($event)->takenBy($participant)->create();

    $this->actingAs($participant, 'participant')
        ->post(route('play.logout'))
        ->assertRedirect(route('guest.home'));

    $this->assertGuest('participant');
    expect(Participant::find($participant->id))->toBeNull()
        ->and($prize->fresh()->participant_id)->toBeNull();
});

test('participant pages send strangers to the join notice', function () {
    $this->get(route('play.index'))->assertRedirect(route('participant.required'));
    $this->get(route('play.scan'))->assertRedirect(route('participant.required'));
});

test('participants cannot win more prizes than the event limit', function () {
    $event = Event::factory()->create(['max_prizes_per_participant' => 2]);
    $participant = Participant::factory()->for($event)->create();
    [$first, $second, $third] = Prize::factory()->count(3)->for($event)->create();

    $this->actingAs($participant, 'participant');

    $this->post(route('treasure.claim', $first));
    $this->post(route('treasure.claim', $second))->assertSessionHas('claim_result', ClaimResult::Won->value);

    $this->post(route('treasure.claim', $third))
        ->assertSessionHas('claim_result', ClaimResult::LimitReached->value);

    expect($participant->prizes()->count())->toBe(2)
        ->and($third->fresh()->participant_id)->toBeNull();
});

test('a participant at the limit sees the limit notice instead of an open button', function () {
    $event = Event::factory()->create(['max_prizes_per_participant' => 1]);
    $participant = Participant::factory()->for($event)->create();
    Prize::factory()->for($event)->takenBy($participant)->create();
    $unopened = Prize::factory()->for($event)->create();
    $miss = Prize::factory()->for($event)->miss()->create();

    $this->actingAs($participant, 'participant');

    $this->get(route('treasure.show', $unopened))
        ->assertSee('獲得上限に達しました')
        ->assertDontSee('開ける！');

    $this->post(route('treasure.claim', $miss))
        ->assertSessionHas('claim_result', ClaimResult::LimitReached->value);

    $this->get(route('play.scan'))
        ->assertSee('獲得上限に達しました')
        ->assertDontSee('カメラを起動');

    $this->get(route('play.index'))
        ->assertSee('獲得上限に達しました')
        ->assertDontSee('QRコードを読み取る</a>', false);
});

test('a participant can still view a prize they already won after reaching the limit', function () {
    $event = Event::factory()->create(['max_prizes_per_participant' => 1]);
    $participant = Participant::factory()->for($event)->create();
    $won = Prize::factory()->for($event)->takenBy($participant)->create(['name' => 'わたしの景品']);

    $this->actingAs($participant, 'participant')
        ->get(route('treasure.show', $won))
        ->assertSee('このお宝はもうあなたのものです');
});

test('misses do not count toward the limit and events without a limit are unlimited', function () {
    $limited = Event::factory()->create(['max_prizes_per_participant' => 1]);
    $player = Participant::factory()->for($limited)->create();
    $this->actingAs($player, 'participant');
    $this->post(route('treasure.claim', Prize::factory()->for($limited)->miss()->create()));
    $this->post(route('treasure.claim', Prize::factory()->for($limited)->create()))
        ->assertSessionHas('claim_result', ClaimResult::Won->value);

    $unlimited = Event::factory()->create(['max_prizes_per_participant' => null]);
    $collector = Participant::factory()->for($unlimited)->create();
    $this->actingAs($collector, 'participant');
    foreach (Prize::factory()->count(5)->for($unlimited)->create() as $prize) {
        $this->post(route('treasure.claim', $prize));
    }

    expect($collector->prizes()->count())->toBe(5)
        ->and($collector->remainingPrizeAllowance())->toBeNull();
});

test('a participant who quits lands on the guest page with a join qr scanner', function () {
    $participant = Participant::factory()->create();

    $this->actingAs($participant, 'participant')
        ->followingRedirects()
        ->post(route('play.logout'))
        ->assertOk()
        ->assertSee('宝探しを終了しました')
        ->assertSee('カメラで読み取る')
        ->assertSee('data-accept-path="/join/"', false)
        ->assertDontSee('主催者ログイン')
        ->assertDontSee('主催者として始める');
});

test('the join notice page also offers the join qr scanner', function () {
    $this->get(route('participant.required'))
        ->assertOk()
        ->assertSee('まずは参加受付をしてね')
        ->assertSee('data-accept-path="/join/"', false)
        ->assertDontSee('主催者ログイン');
});

test('the treasure scanner only accepts treasure qr codes', function () {
    $participant = Participant::factory()->create();

    $this->actingAs($participant, 'participant')
        ->get(route('play.scan'))
        ->assertOk()
        ->assertSee('data-accept-path="/treasure/"', false);
});

test('the logo on guest pages links back to the guest page', function () {
    $this->get(route('guest.home'))
        ->assertSee('href="'.route('guest.home').'" class="flex items-center gap-2', false);
});

test('the treasure scanner starts the camera as soon as the page opens', function () {
    $this->actingAs(Participant::factory()->create(), 'participant')
        ->get(route('play.scan'))
        ->assertSee('data-autostart="true"', false);

    $this->get(route('guest.home'))
        ->assertSee('data-autostart="false"', false)
        ->assertSee('カメラで読み取る');
});

test('misses count toward the limit when the event says so, once per miss', function () {
    $event = Event::factory()->withPrizeLimit(2, countMisses: true)->create();
    $participant = Participant::factory()->for($event)->create();
    $miss = Prize::factory()->for($event)->miss()->create();
    [$first, $second] = Prize::factory()->count(2)->for($event)->create();

    $this->actingAs($participant, 'participant');

    $this->post(route('treasure.claim', $miss))->assertSessionHas('claim_result', ClaimResult::Miss->value);
    $this->post(route('treasure.claim', $miss))->assertSessionHas('claim_result', ClaimResult::Miss->value);
    expect($participant->fresh()->remainingPrizeAllowance())->toBe(1);

    $this->post(route('treasure.claim', $first))->assertSessionHas('claim_result', ClaimResult::Won->value);
    $this->post(route('treasure.claim', $second))->assertSessionHas('claim_result', ClaimResult::LimitReached->value);

    expect($second->fresh()->participant_id)->toBeNull();
    $this->get(route('play.index'))->assertSee('開けたハズレ 1個');
});

test('misses are not recorded when the event does not count them', function () {
    $event = Event::factory()->withPrizeLimit(1)->create();
    $participant = Participant::factory()->for($event)->create();
    $miss = Prize::factory()->for($event)->miss()->create();

    $this->actingAs($participant, 'participant')->post(route('treasure.claim', $miss));

    expect($participant->foundMisses()->count())->toBe(0)
        ->and($participant->fresh()->remainingPrizeAllowance())->toBe(1);
});

test('resetting acquisitions and ending the event clear the recorded misses', function () {
    $event = Event::factory()->withPrizeLimit(1, countMisses: true)->create();
    $participant = Participant::factory()->for($event)->create();
    $miss = Prize::factory()->for($event)->miss()->create();
    $participant->foundMisses()->attach($miss);

    $event->resetAcquisitions();
    expect($participant->foundMisses()->count())->toBe(0);

    $participant->foundMisses()->attach($miss);
    $event->end();
    expect(DB::table('participant_misses')->count())->toBe(0);
});

test('participating guests are kept on their page instead of the organizer top page', function () {
    $participant = Participant::factory()->create();

    $this->actingAs($participant, 'participant');

    $this->get(route('home'))->assertRedirect(route('play.index'));
    $this->get(route('play.index'))
        ->assertSee('href="'.route('play.index').'" class="flex items-center gap-2', false);
});

test('visitors see the top page with links to the organizer pages', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('主催者として始める')
        ->assertSee(route('register'));
});

test('someone logged in as both organizer and participant sees each side separately', function () {
    $organizer = User::factory()->create();
    $this->actingAs(Participant::factory()->create(), 'participant')->actingAs($organizer, 'web');

    $this->get(route('events.index'))
        ->assertSee('主催者メニュー')
        ->assertDontSee('QRを読む')
        ->assertDontSee(route('play.index'));

    $this->get(route('home'))->assertRedirect(route('play.index'));

    $this->get(route('play.index'))
        ->assertSee('QRを読む')
        ->assertDontSee('主催者メニュー')
        ->assertDontSee(route('logout'));
});

test('someone who scanned a treasure before joining gets the guest page instead of the organizer top page', function () {
    $prize = Prize::factory()->create();

    $this->get(route('treasure.show', $prize))
        ->assertViewIs('treasure.need-join')
        ->assertDontSee('主催者ログイン')
        ->assertSee('href="'.route('guest.home').'" class="flex items-center gap-2', false);

    $this->get(route('home'))->assertRedirect(route('guest.home'));
    $this->get(route('guest.home'))
        ->assertSee('QRコードを読み取って')
        ->assertSee('カメラで読み取る');
});

test('the join page is shown without organizer links', function () {
    $event = Event::factory()->create();

    $this->get(route('join', $event))
        ->assertOk()
        ->assertDontSee('主催者ログイン')
        ->assertSee('href="'.route('guest.home').'" class="flex items-center gap-2', false);

    $this->get(route('home'))->assertRedirect(route('guest.home'));
});

test('a logged in organizer can still use the organizer pages after opening a join page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create();

    $this->actingAs($user)->get(route('join', $event))->assertOk()->assertDontSee('主催者メニュー');

    $this->get(route('host'))->assertRedirect('/host/events');
    $this->get(route('events.index'))->assertOk()->assertSee('主催者メニュー');
});

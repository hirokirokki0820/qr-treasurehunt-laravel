<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;

function redemptionSetup(): array
{
    $organizer = User::factory()->create();
    $event = Event::factory()->for($organizer)->create();
    $participant = Participant::factory()->for($event)->create(['nickname' => 'たろう']);
    $first = Prize::factory()->for($event)->takenBy($participant)->create(['name' => 'お菓子']);
    $second = Prize::factory()->for($event)->takenBy($participant)->create(['name' => 'シール']);

    return [$organizer, $event, $participant, $first, $second];
}

test('organizers can redeem prizes one by one, all at once, and undo a redemption', function () {
    [$organizer, $event, $participant, $first, $second] = redemptionSetup();

    $this->actingAs($organizer)
        ->post(route('events.prizes.redeem', [$event, $first]))
        ->assertRedirect(route('events.participants.show', [$event, $participant]));

    expect($first->fresh()->isRedeemed())->toBeTrue()
        ->and($second->fresh()->isRedeemed())->toBeFalse();

    $this->post(route('events.participants.redeem', [$event, $participant]));
    expect($participant->unredeemedPrizeCount())->toBe(0);

    $this->delete(route('events.prizes.redeem.cancel', [$event, $first]));
    expect($first->fresh()->isRedeemed())->toBeFalse();

    $this->get(route('events.participants', $event))->assertSee('未 1個');
});

test('participants see redemption status and their redemption qr code', function () {
    [, , $participant, $first] = redemptionSetup();
    $first->forceFill(['redeemed_at' => now()])->save();

    $this->actingAs($participant, 'participant')
        ->get(route('play.index'))
        ->assertSee('✓ 引き換え済み')
        ->assertSee('未引き換え')
        ->assertSee(route('redemption.lookup', $participant->redeem_token));
});

test('scanning a redemption qr code opens the participant page for the organizer only', function () {
    [$organizer, $event, $participant] = redemptionSetup();

    $this->actingAs($organizer)
        ->get(route('redemption.lookup', $participant->redeem_token))
        ->assertRedirect(route('events.participants.show', [$event, $participant]));

    $this->actingAs(User::factory()->create())
        ->get(route('redemption.lookup', $participant->redeem_token))
        ->assertForbidden();

    $this->actingAs($organizer)->get(route('redemption.scan'))->assertOk()->assertSee('data-accept-path="/host/redeem/"', false);
});

test('other organizers cannot redeem prizes', function () {
    [, $event, , $first] = redemptionSetup();

    $this->actingAs(User::factory()->create())
        ->post(route('events.prizes.redeem', [$event, $first]))
        ->assertForbidden();

    expect($first->fresh()->isRedeemed())->toBeFalse();
});

test('participants cannot mark their own prizes as redeemed', function () {
    [, $event, $participant, $first] = redemptionSetup();

    $this->actingAs($participant, 'participant')
        ->post(route('events.participants.redeem', [$event, $participant]))
        ->assertRedirect(route('login'));

    expect($first->fresh()->isRedeemed())->toBeFalse();
});

test('redemption records are cleared by resets, ending the event and quitting', function () {
    [$organizer, $event, $participant, $first, $second] = redemptionSetup();
    $participant->redeemAllPrizes();

    $event->resetAcquisitions();
    expect($first->fresh()->redeemed_at)->toBeNull();

    $second->refresh()->forceFill(['participant_id' => $participant->id, 'acquired_at' => now(), 'redeemed_at' => now()])->save();
    $this->actingAs($participant, 'participant')->post(route('play.logout'));

    expect($second->fresh())
        ->participant_id->toBeNull()
        ->acquired_at->toBeNull()
        ->redeemed_at->toBeNull();
});

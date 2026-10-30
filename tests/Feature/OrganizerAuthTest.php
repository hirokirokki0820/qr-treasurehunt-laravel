<?php

use App\Models\User;

test('organizers can register with an account name and no email', function () {
    $this->post(route('register'), [
        'name' => 'organizer1',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ])->assertRedirect(route('events.index'));

    $user = User::firstWhere('name', 'organizer1');

    expect($user)->not->toBeNull()
        ->and($user->email)->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('account names must be unique ascii alphanumerics', function (string $name) {
    User::factory()->create(['name' => 'taken']);

    $this->post(route('register'), [
        'name' => $name,
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ])->assertSessionHasErrors('name');

    $this->assertGuest();
})->with(['taken', 'たろう', 'has space', 'ab']);

test('organizers can log in and out', function () {
    $user = User::factory()->create(['name' => 'organizer1']);

    $this->post(route('login'), ['name' => 'organizer1', 'password' => 'password'])
        ->assertRedirect(route('events.index'));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('login fails with a wrong password', function () {
    User::factory()->create(['name' => 'organizer1']);

    $this->post(route('login'), ['name' => 'organizer1', 'password' => 'wrong'])
        ->assertSessionHasErrors('name');

    $this->assertGuest();
});

test('guests are sent to the login page from organizer pages', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

test('the organizer top page and the logo take a logged in organizer to the organizer menu', function () {
    $user = User::factory()->create();

    expect(route('host'))->toEndWith('/host')
        ->and(route('events.index'))->toEndWith('/host/events')
        ->and(route('login'))->toEndWith('/host/login');

    $this->actingAs($user)->get(route('host'))->assertRedirect('/host/events');

    $this->get(route('events.index'))
        ->assertSee('href="'.route('events.index').'" class="flex items-center gap-2', false);
});

test('organizer pages use their own session cookie so participants on the same browser stay separate', function () {
    $baseCookie = config('session.cookie');

    $hostCookie = collect($this->get(route('login'))->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === $baseCookie.'_host');

    expect($hostCookie)->not->toBeNull()
        ->and($hostCookie->getPath())->toBe('/host');

    $guestCookie = collect($this->get(route('guest.home'))->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === $baseCookie);

    expect($guestCookie)->not->toBeNull()
        ->and($guestCookie->getPath())->toBe('/');
});

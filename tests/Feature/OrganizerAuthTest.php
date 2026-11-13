<?php

use App\Models\User;
use App\Services\KobappAuth;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.kobapp.url' => 'https://kobapp.example.test',
        'services.kobapp.client_id' => 'qr-takarasagashi',
        'services.kobapp.client_secret' => 'test-secret-0123456789abcdefghijklmnopqrstuvwxyz',
        'services.kobapp.redirect_uri' => null,
    ]);
});

/**
 * 「kobapp でログイン」を押して、kobapp.jp へ送られる URL の項目を返す。
 *
 * @return array<string, string>
 */
function startKobappLogin(): array
{
    $location = test()->get(route('login.kobapp'))->assertRedirect()->headers->get('Location');

    expect($location)->toStartWith('https://kobapp.example.test/sso/authorize?');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return $query;
}

function fakeKobappToken(array $body = ['sub' => '42', 'email' => 'host@example.com', 'email_verified' => true], int $status = 200): void
{
    Http::fake(['kobapp.example.test/sso/token' => Http::response($body, $status)]);
}

test('the login page offers only the kobapp login button', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('kobapp でログイン')
        ->assertSee(route('login.kobapp'))
        ->assertSee('https://kobapp.example.test/register')
        ->assertDontSee('name="password"', false);
});

test('organizers can no longer register or log in with an account name', function () {
    $this->get('/host/register')->assertNotFound();
    $this->post('/host/login', ['name' => 'organizer1', 'password' => 'password'])->assertStatus(405);
    $this->assertGuest();
});

test('starting the login sends the organizer to kobapp with state and PKCE', function () {
    $query = startKobappLogin();

    expect($query['response_type'])->toBe('code')
        ->and($query['client_id'])->toBe('qr-takarasagashi')
        ->and($query['redirect_uri'])->toBe(route('login.kobapp.callback'))
        ->and($query['code_challenge_method'])->toBe('S256')
        ->and(strlen($query['state']))->toBe(40);

    $pending = session('kobapp_auth');
    expect($pending['state'])->toBe($query['state'])
        ->and(KobappAuth::challengeFor($pending['verifier']))->toBe($query['code_challenge']);
});

test('a first-time organizer is created from the kobapp account and logged in', function () {
    fakeKobappToken();
    $query = startKobappLogin();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => $query['state']]))
        ->assertRedirect(route('events.index'));

    $user = User::firstWhere('kobapp_user_id', '42');
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('host@example.com');
    $this->assertAuthenticatedAs($user);

    Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://kobapp.example.test/sso/token'
        && $request['grant_type'] === 'authorization_code'
        && $request['code'] === 'the-code'
        && $request['client_id'] === 'qr-takarasagashi'
        && $request['client_secret'] === 'test-secret-0123456789abcdefghijklmnopqrstuvwxyz'
        && $request['redirect_uri'] === route('login.kobapp.callback')
        && KobappAuth::challengeFor($request['code_verifier']) === $query['code_challenge']);
});

test('a returning organizer is matched by kobapp user id and gets the latest email', function () {
    $user = User::factory()->create(['kobapp_user_id' => '42', 'email' => 'old@example.com']);
    fakeKobappToken(['sub' => '42', 'email' => 'new@example.com', 'email_verified' => true]);
    $query = startKobappLogin();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => $query['state']]))
        ->assertRedirect(route('events.index'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->email)->toBe('new@example.com')
        ->and(User::count())->toBe(1);
});

test('a wrong state is rejected without asking kobapp', function () {
    Http::fake();
    startKobappLogin();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => 'forged-state']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    Http::assertNothingSent();
    $this->assertGuest();
});

test('a callback without starting the login is rejected', function () {
    Http::fake();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => 'any']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    Http::assertNothingSent();
    $this->assertGuest();
});

test('the same callback cannot be used twice', function () {
    fakeKobappToken();
    $query = startKobappLogin();
    $callback = route('login.kobapp.callback', ['code' => 'the-code', 'state' => $query['state']]);

    $this->get($callback)->assertRedirect(route('events.index'));
    $this->post(route('logout'));

    $this->get($callback)->assertRedirect(route('login'))->assertSessionHas('error');
    $this->assertGuest();
});

test('an old login attempt expires', function () {
    Http::fake();
    $query = startKobappLogin();

    $this->travel(11)->minutes();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => $query['state']]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');
    Http::assertNothingSent();
});

test('cancelling on kobapp shows a message', function () {
    Http::fake();
    $query = startKobappLogin();

    $this->get(route('login.kobapp.callback', ['error' => 'access_denied', 'state' => $query['state']]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'ログインをキャンセルしました。');
    Http::assertNothingSent();
});

test('a failed or incomplete answer from kobapp does not log anyone in', function (array $body, int $status) {
    fakeKobappToken($body, $status);
    $query = startKobappLogin();

    $this->get(route('login.kobapp.callback', ['code' => 'the-code', 'state' => $query['state']]))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'invalid grant' => [['error' => 'invalid_grant'], 400],
    'no user id' => [['email' => 'host@example.com', 'email_verified' => true], 200],
    'email not verified' => [['sub' => '42', 'email' => 'host@example.com', 'email_verified' => false], 200],
    'server error' => [[], 500],
]);

test('organizers can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
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

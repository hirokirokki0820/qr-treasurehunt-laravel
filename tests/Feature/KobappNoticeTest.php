<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::forget('kobapp-site-notice');
    config(['services.kobapp.url' => 'https://kobapp.example.test']);
});

test('organizers see the notice published on kobapp.jp, escaped', function () {
    Http::fake(['kobapp.example.test/api/site-notice' => Http::response(['notice' => ['message' => '<b>つながりにくく</b>なっています', 'level' => 'warning', 'updated_at' => now()->toIso8601String()]])]);
    $owner = User::factory()->create();

    $this->actingAs($owner)->get(route('events.index'))
        ->assertSee('data-kobapp-notice="warning"', false)
        ->assertSee('&lt;b&gt;つながりにくく&lt;/b&gt;なっています', false);

    // 1分のあいだは、取りに行き直さない
    $this->get(route('events.index'));
    Http::assertSentCount(1);
});

test('participants do not see the notice', function () {
    Http::fake(['kobapp.example.test/api/site-notice' => Http::response(['notice' => ['message' => '障害です', 'level' => 'warning']])]);
    $participant = Participant::factory()->for(Event::factory())->create();

    $this->actingAs($participant, 'participant')->get(route('play.index'))->assertDontSee('data-kobapp-notice', false);
});

test('nothing is shown when kobapp.jp has no notice, is down, or returns something odd', function (Closure $response) {
    Http::fake(['kobapp.example.test/*' => $response]);

    $this->actingAs(User::factory()->create())->get(route('events.index'))->assertOk()->assertDontSee('data-kobapp-notice', false);
})->with([
    'no notice' => fn () => fn () => Http::response(['notice' => null]),
    'server error' => fn () => fn () => Http::response('', 500),
    'odd level' => fn () => fn () => Http::response(['notice' => ['message' => 'x', 'level' => 'danger']]),
    'timeout' => fn () => fn () => throw new ConnectionException('timeout'),
]);

<?php

use App\Models\Event;
use App\Models\User;

test('unknown pages show the custom not found page', function () {
    $this->get('/treasure/not-a-real-token')
        ->assertNotFound()
        ->assertSee('ページが<br>見つかりません', false)
        ->assertSee('QRコードから来た場合は');
});

test('organizers opening another organizer\'s event see the custom forbidden page', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.show', $event))
        ->assertForbidden()
        ->assertSee('ERROR 403');
});

test('every custom error page renders on its own', function (int $code, string $heading) {
    $this->withoutVite();

    expect(view("errors.{$code}")->render())
        ->toContain("ERROR {$code}")
        ->toContain($heading);
})->with([
    [403, '表示できません'],
    [404, '見つかりません'],
    [419, '有効期限が切れました'],
    [429, '集中しています'],
    [500, '発生しました'],
]);

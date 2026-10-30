<?php

test('visitors see the maintenance page while the site is down', function () {
    $this->app->maintenanceMode()->activate([]);

    $this->get(route('home'))
        ->assertServiceUnavailable()
        ->assertSee('メンテナンス中です')
        ->assertSee('ページを再読み込み');

    $this->app->maintenanceMode()->deactivate();

    $this->get(route('home'))->assertOk();
});

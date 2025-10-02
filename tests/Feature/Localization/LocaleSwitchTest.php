<?php

use Illuminate\Support\Facades\Lang;

it('renders the landing page in the default locale', function () {
    $this->startSession();

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(Lang::get('landing.hero.headline'));
});

it('switches to Arabic when requested', function () {
    $this->startSession();

    $response = $this->post('/locale/ar');

    $response->assertRedirect('/');

    $this->followRedirects($response)
        ->assertSee('منصة الامتثال لنظام حماية الأجور')
        ->assertSee('البيئة متصلة');
});

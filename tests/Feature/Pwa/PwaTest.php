<?php

it('serves the PWA manifest with required metadata', function () {
    $this->startSession();

    $response = $this->get('/manifest.webmanifest');

    $response->assertOk();

    $manifest = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

    expect($manifest)
        ->name->toBe('WPS Payroll Compliance')
        ->short_name->toBe('WPS Payroll')
        ->display->toBe('standalone');
});

it('serves the offline page for caching', function () {
    $this->startSession();

    $this->get('/offline')->assertOk()->assertSee('offline', escape: false);
});

it('exposes the service worker script', function () {
    $this->startSession();

    $this->get('/service-worker.js')
        ->assertOk()
        ->assertSee('CACHE_NAME', escape: false);
});

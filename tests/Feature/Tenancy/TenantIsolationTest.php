<?php

use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

it('blocks access when requesting a tenant route from an unknown domain', function () {
    createTenantForTest('acme.test');

    try {
        $this->get('http://globex.test/admin');
        $this->fail('Expected tenant resolution to fail for unknown domain.');
    } catch (TenantCouldNotBeIdentifiedOnDomainException $exception) {
        expect($exception)->toBeInstanceOf(TenantCouldNotBeIdentifiedOnDomainException::class);
    }
});

it('allows access from the matching tenant domain', function () {
    createTenantForTest('acme.test');

    $this->withExceptionHandling();

    $this->get('http://acme.test/health')->assertOk();
});

<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('captures importer and validation metrics for a tenant', function (): void {
    Storage::fake('local');

    $tenant = createTenantForTest('baseline-'.Str::random(6).'.test');

    Artisan::call('queue:baseline', [
        'tenant' => $tenant->id,
        '--employees' => 3,
        '--runs' => 1,
        '--output' => 'metrics/test-baseline.json',
    ]);

    Storage::disk('local')->assertExists('metrics/test-baseline.json');

    $payload = json_decode(Storage::disk('local')->get('metrics/test-baseline.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)
        ->toHaveKey('tenant_id', $tenant->id)
        ->and($payload['importer']['runs'])->toBe(1)
        ->and($payload['importer']['durations_ms'])->toHaveCount(1)
        ->and($payload['validation']['runs'])->toBe(1)
        ->and($payload['validation']['durations_ms'])->toHaveCount(1);
});

<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use App\Support\Validation\BatchValidationManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('testing.sqlite'),
        'database.connections.sqlite.url' => null,
        'database.connections.tenant.driver' => 'sqlite',
        'database.connections.tenant.url' => null,
        'tenancy.database.central_connection' => 'sqlite',
    ]);

    if (! file_exists(database_path('testing.sqlite'))) {
        touch(database_path('testing.sqlite'));
    }

    static $migrated = false;

    if (! $migrated) {
        Artisan::call('migrate', ['--force' => true]);
        $migrated = true;
    }
});

it('stores validation metadata when a batch passes all rules', function () {
    $tenant = createTenantForTest('acme-validation-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    Employee::factory()->count(2)->for($company)->create([
        'salary' => 8500,
        'currency' => 'AED',
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-PASS-001',
        'status' => 'draft',
    ]);

    $report = app(BatchValidationManager::class)->run($batch);

    $batch->refresh();

    $exceptions = PayrollException::query()->count();
    $metadataValue = $batch->metadata;
    $metadata = is_array($metadataValue)
        ? $metadataValue
        : ($metadataValue ? $metadataValue->toArray() : []);

    expect($report->failures())->toHaveCount(0)
        ->and(Arr::get($metadata, 'validation.status'))->toBe('passed')
        ->and(Arr::get($metadata, 'validation.summary.total'))->toBeGreaterThan(0)
        ->and(Arr::get($metadata, 'audit.last_event.event'))->toBe('validation.run')
        ->and($exceptions)->toBe(0);

    Tenancy::end();
});

it('marks validation as failed when any rule fails', function () {
    $tenant = createTenantForTest('globex-validation-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    Employee::factory()->count(1)->for($company)->create([
        'salary' => -100,
        'currency' => 'USD',
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-FAIL-001',
        'status' => 'draft',
        'metadata' => ['notes' => 'Validation should fail'],
    ]);

    $report = app(BatchValidationManager::class)->run($batch);

    $batch->refresh();

    $exceptions = PayrollException::query()->get();
    $metadataValue = $batch->metadata;
    $metadata = is_array($metadataValue)
        ? $metadataValue
        : ($metadataValue ? $metadataValue->toArray() : []);

    expect($report->failures()->count())->toBeGreaterThan(0)
        ->and(Arr::get($metadata, 'validation.status'))->toBe('failed')
        ->and(Arr::get($metadata, 'audit.last_event.event'))->toBe('validation.run')
        ->and(collect(Arr::get($metadata, 'validation.results', []))
            ->pluck('rule_id')
            ->all())
        ->toContain('uae-wps-salary-minimum')
        ->and($exceptions->count())->toBeGreaterThan(0)
        ->and($exceptions->first()->status)->toBe('open');

    Tenancy::end();
});

it('stores selected KSA rule sets in validation metadata', function () {
    $tenant = createTenantForTest('ksa-validation-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    Employee::factory()->count(1)->for($company)->create([
        'salary' => 8100,
        'currency' => 'SAR',
        'metadata' => [
            'mudad' => [
                'national_id' => '1234567890',
                'contract_type' => 'full_time',
                'bank_iban' => 'SA'.str_repeat('2', 22),
            ],
        ],
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'MUDAD-BATCH-1',
        'status' => 'draft',
        'metadata' => [
            'mudad' => [
                'portal_reference' => 'PRT-200',
            ],
        ],
    ]);

    $report = app(BatchValidationManager::class)->run($batch, ['ksa-mudad-sandbox']);

    $batch->refresh();

    $metadataValue = $batch->metadata;
    $metadata = is_array($metadataValue)
        ? $metadataValue
        : ($metadataValue ? $metadataValue->toArray() : []);

    expect($report->failures())->toHaveCount(0)
        ->and(Arr::get($metadata, 'validation.rule_sets'))->toBe(['ksa-mudad-sandbox'])
        ->and(Arr::get($metadata, 'validation.status'))->toBe('passed');

    Tenancy::end();
});

it('resolves exceptions once validation passes on subsequent runs', function () {
    $tenant = createTenantForTest('acme-exception-resolve-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    $employee = Employee::factory()->for($company)->create([
        'salary' => -50,
        'currency' => 'USD',
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-RESOLVE-1',
        'status' => 'draft',
    ]);

    app(BatchValidationManager::class)->run($batch);

    $existing = PayrollException::query()->first();

    expect($existing)->not->toBeNull();

    $employee->update([
        'salary' => 5500,
        'currency' => 'AED',
    ]);

    app(BatchValidationManager::class)->run($batch);

    $existing->refresh();

    expect($existing->status)->toBe('resolved')
        ->and($existing->resolved_at)->not->toBeNull();

    Tenancy::end();
});

<?php

use App\Jobs\GenerateSifExport;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Support\Sif\SifExportManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
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

it('queues a SIF export and records metadata', function () {
    Queue::fake();

    $tenant = createTenantForTest('acme-sif-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    Employee::factory()->count(2)->for($company)->create([
        'salary' => 6000,
        'currency' => 'AED',
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-EXPORT-1',
        'status' => 'draft',
    ]);

    $entry = app(SifExportManager::class)->queue($batch, 'uae-wps-standard-v1');

    $batch->refresh();

    Queue::assertPushed(GenerateSifExport::class, function (GenerateSifExport $job) use ($tenant, $batch, $entry) {
        return $job->tenantId === $tenant->getTenantKey()
            && $job->templateKey === 'uae-wps-standard-v1'
            && $job->batch['reference'] === $batch->reference
            && count($job->employees) === 2
            && $job->queuedAt === $entry['queued_at'];
    });

    Tenancy::end();

    expect($entry['template'])->toBe('uae-wps-standard-v1')
        ->and($entry['status'])->toBe('queued')
        ->and($entry['template_label'])->toBe('UAE WPS Standard')
        ->and($entry['integrations'])->toBe([])
        ->and($entry['download_url'])->toBeNull()
        ->and(data_get($batch->metadata, 'last_export.template'))->toBe('uae-wps-standard-v1')
        ->and(data_get($batch->metadata, 'exports.0.status'))->toBe('queued')
        ->and(data_get($batch->metadata, 'exports.0.integrations'))->toBe([])
        ->and(data_get($batch->metadata, 'audit.last_event.event'))->toBe('sif.queued');
});

it('throws when no employees are available for export', function () {
    Queue::fake();

    $tenant = createTenantForTest('empty-sif-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-EMPTY-1',
        'status' => 'draft',
    ]);

    expect(fn () => app(SifExportManager::class)->queue($batch, 'uae-wps-standard-v1'))
        ->toThrow(\RuntimeException::class);

    Tenancy::end();
});

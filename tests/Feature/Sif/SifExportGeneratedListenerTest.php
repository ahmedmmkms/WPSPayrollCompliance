<?php

use App\Events\SifExportGenerated;
use App\Listeners\HandleSifExportGenerated;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Support\Sif\SifExportManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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
        'sif.templates_path' => resource_path('sif/templates'),
        'sif.disk' => 'local',
        'queue.default' => 'sync',
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

it('updates metadata and batch status when a SIF export becomes available', function () {
    Storage::fake('local');

    $tenant = createTenantForTest('listener-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    Employee::factory()->count(2)->for($company)->create([
        'salary' => 5000,
        'currency' => 'AED',
    ]);

    $batch = PayrollBatch::factory()->for($company)->create([
        'reference' => 'BATCH-LISTENER-1',
        'status' => 'draft',
    ]);

    $entry = app(SifExportManager::class)->queue($batch, 'uae-wps-standard-v1');

    $batch->refresh();

    Tenancy::end();

    $event = new SifExportGenerated(
        tenantId: $tenant->getKey(),
        templateKey: 'uae-wps-standard-v1',
        batchId: $batch->getKey(),
        disk: 'local',
        path: 'sif/'.$tenant->getKey().'/BATCH-LISTENER-1-uae-wps-1.0.0.sif',
        filename: 'BATCH-LISTENER-1-uae-wps-1.0.0.sif',
        queuedAt: $entry['queued_at'],
        integrations: [
            'mudad' => [
                'id' => 'submission-321',
                'status' => 'queued',
            ],
        ],
    );

    app(HandleSifExportGenerated::class)->handle($event);

    Tenancy::initialize($tenant);

    $batch->refresh();

    expect(data_get($batch->metadata, 'last_export.status'))->toBe('available')
        ->and(data_get($batch->metadata, 'last_export.filename'))->toBe('BATCH-LISTENER-1-uae-wps-1.0.0.sif')
        ->and(data_get($batch->metadata, 'last_export.path'))->toBe('sif/'.$tenant->getKey().'/BATCH-LISTENER-1-uae-wps-1.0.0.sif')
        ->and(data_get($batch->metadata, 'last_export.integrations.mudad.id'))->toBe('submission-321')
        ->and(data_get($batch->metadata, 'last_export.integrations.mudad.status'))->toBe('queued')
        ->and(data_get($batch->metadata, 'audit.last_event.event'))->toBe('sif.generated')
        ->and($batch->status)->toBe('queued');

    Tenancy::end();
});

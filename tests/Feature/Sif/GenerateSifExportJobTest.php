<?php

use App\Events\SifExportGenerated;
use App\Jobs\GenerateSifExport;
use App\Support\Mudad\MudadAdapter;
use App\Support\Mudad\MudadSubmissionResult;
use App\Support\Sif\SifGenerator;
use App\Support\Sif\SifTemplateRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'sif.templates_path' => resource_path('sif/templates'),
        'sif.disk' => 'local',
    ]);
});

afterEach(function () {
    \Mockery::close();
});

it('stores a generated SIF file and dispatches an event', function () {
    Event::fake([
        SifExportGenerated::class,
    ]);

    Storage::fake('local');

    $batch = [
        'id' => 'batch-1',
        'company_id' => 'company-1',
        'reference' => 'BATCH-002',
        'scheduled_for' => Carbon::parse('2025-10-03'),
    ];

    $employees = [
        [
            'external_id' => 'EMP010',
            'salary' => 6200,
            'currency' => 'AED',
        ],
        [
            'external_id' => 'EMP011',
            'salary' => 5800,
            'currency' => 'AED',
        ],
    ];

    $queuedAt = now()->toISOString();

    $job = new GenerateSifExport('tenant-1', 'uae-wps-standard-v1', $batch, $employees, $queuedAt);

    $adapter = \Mockery::mock(MudadAdapter::class);
    $adapter->shouldNotReceive('submit');

    $job->handle(SifTemplateRepository::fromConfig(), new SifGenerator, $adapter);

    Storage::disk('local')->assertExists('sif/tenant-1/BATCH-002-uae-wps-1.0.0.sif');

    Event::assertDispatched(SifExportGenerated::class, function (SifExportGenerated $event) use ($queuedAt) {
        return $event->tenantId === 'tenant-1'
            && $event->templateKey === 'uae-wps-standard-v1'
            && $event->batchId === 'batch-1'
            && $event->filename === 'BATCH-002-uae-wps-1.0.0.sif'
            && $event->queuedAt === $queuedAt
            && $event->integrations === [];
    });

    expect($job->storedPath)->toBe('sif/tenant-1/BATCH-002-uae-wps-1.0.0.sif');
});

it('submits Mudad exports through the adapter', function () {
    Event::fake([
        SifExportGenerated::class,
    ]);

    Storage::fake('local');

    $batch = [
        'id' => 'batch-ksa-1',
        'company_id' => 'company-ksa',
        'reference' => 'MUDAD-001',
        'scheduled_for' => Carbon::parse('2025-11-05'),
        'metadata' => [
            'mudad' => [
                'portal_reference' => 'PORTAL-123',
            ],
        ],
    ];

    $employees = [
        [
            'id' => 'emp-01',
            'company_id' => 'company-ksa',
            'external_id' => 'EMP001',
            'salary' => 7200,
            'currency' => 'SAR',
            'metadata' => [
                'mudad' => [
                    'national_id' => '1234567890',
                    'contract_type' => 'full_time',
                    'bank_iban' => 'SA'.str_repeat('0', 22),
                ],
            ],
        ],
    ];

    $queuedAt = now()->toISOString();

    $job = new GenerateSifExport('tenant-ksa', 'ksa-mudad-sandbox-v1', $batch, $employees, $queuedAt);

    $adapter = \Mockery::mock(MudadAdapter::class);
    $adapter->shouldReceive('submit')
        ->once()
        ->andReturn(new MudadSubmissionResult('submission-123', 'queued', [
            'id' => 'submission-123',
            'status' => 'queued',
        ]));

    $job->handle(SifTemplateRepository::fromConfig(), new SifGenerator, $adapter);

    Storage::disk('local')->assertExists('sif/tenant-ksa/MUDAD-001-ksa-mudad-1.0.0.sif');

    Event::assertDispatched(SifExportGenerated::class, function (SifExportGenerated $event) use ($queuedAt) {
        return $event->templateKey === 'ksa-mudad-sandbox-v1'
            && ($event->integrations['mudad']['id'] ?? null) === 'submission-123'
            && $event->integrations['mudad']['status'] === 'queued'
            && $event->queuedAt === $queuedAt;
    });
});

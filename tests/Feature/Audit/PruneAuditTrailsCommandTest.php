<?php

use App\Models\Company;
use App\Models\PayrollBatch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

it('supports dry-run execution without mutating audit trails', function (): void {
    config()->set('audit.retention.max_events', 100);
    config()->set('audit.retention.max_age_days', 30);

    $tenant = createTenantForTest('audit-prune-dry-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::create([
        'tenant_id' => $tenant->id,
        'name' => 'Dry Run Co',
        'trade_license' => 'TL-DRY',
        'contact_email' => 'ops@dry.test',
    ]);

    $metadata = [
        'audit' => [
            'trail' => [
                [
                    'event' => 'older',
                    'occurred_at' => now()->subDays(90)->toISOString(),
                    'payload' => [],
                ],
                [
                    'event' => 'recent',
                    'occurred_at' => now()->subDays(5)->toISOString(),
                    'payload' => [],
                ],
            ],
            'last_event' => [
                'event' => 'recent',
                'occurred_at' => now()->subDays(5)->toISOString(),
                'payload' => [],
            ],
        ],
    ];

    $batch = PayrollBatch::create([
        'company_id' => $company->id,
        'reference' => strtoupper(Str::random(12)),
        'scheduled_for' => now()->addDay(),
        'status' => 'draft',
        'metadata' => $metadata,
    ]);

    Tenancy::end();

    Artisan::call('audit:prune', [
        'tenant' => [$tenant->id],
        '--dry-run' => true,
    ]);

    Tenancy::initialize($tenant);

    $fresh = $batch->fresh();

    expect(data_get($fresh->metadata, 'audit.trail'))
        ->toHaveCount(2)
        ->and(data_get($fresh->metadata, 'audit.trail.0.event'))->toBe('older');

    Tenancy::end();
});

it('applies retention rules and removes stale events when not in dry-run mode', function (): void {
    config()->set('audit.retention.max_events', 5);
    config()->set('audit.retention.max_age_days', 30);

    $tenant = createTenantForTest('audit-prune-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::create([
        'tenant_id' => $tenant->id,
        'name' => 'Retention Corp',
        'trade_license' => 'TL-AUD',
        'contact_email' => 'ops@retention.test',
    ]);

    $metadata = [
        'audit' => [
            'trail' => [
                [
                    'event' => 'stale',
                    'occurred_at' => now()->subDays(120)->toISOString(),
                    'payload' => [],
                ],
                [
                    'event' => 'recent',
                    'occurred_at' => now()->subDays(2)->toISOString(),
                    'payload' => [],
                ],
            ],
            'last_event' => [
                'event' => 'recent',
                'occurred_at' => now()->subDays(2)->toISOString(),
                'payload' => [],
            ],
        ],
    ];

    $batch = PayrollBatch::create([
        'company_id' => $company->id,
        'reference' => strtoupper(Str::random(12)),
        'scheduled_for' => now()->addDay(),
        'status' => 'draft',
        'metadata' => $metadata,
    ]);

    Tenancy::end();

    Artisan::call('audit:prune', [
        'tenant' => [$tenant->id],
    ]);

    Tenancy::initialize($tenant);

    $fresh = $batch->fresh();

    $trail = data_get($fresh->metadata, 'audit.trail');

    expect($trail)
        ->toHaveCount(1)
        ->and($trail[0]['event'])->toBe('recent');

    expect(data_get($fresh->metadata, 'audit.last_event.event'))->toBe('recent');

    Tenancy::end();
});

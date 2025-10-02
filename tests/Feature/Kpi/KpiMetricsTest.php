<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
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
        'cache.default' => 'array',
        'session.driver' => 'array',
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

it('returns throughput trend grouped by day and status', function () {
    $tenant = createTenantForTest('kpi-throughput-'.Str::random(6).'.test');
    $domain = $tenant->domains()->first()->domain;

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    expect(Route::has('tenant.kpi.throughput'))->toBeTrue();

    $dayOne = now()->subDays(2)->setHour(10)->setMinute(0);
    $dayTwo = now()->subDay()->setHour(11)->setMinute(30);
    $dayThree = now()->setHour(12)->setMinute(15);

    $draftOne = PayrollBatch::factory()
        ->for($company)
        ->create(['status' => 'draft']);

    $draftOne->forceFill([
        'created_at' => $dayOne,
        'updated_at' => $dayOne,
    ])->saveQuietly();

    $draftTwo = PayrollBatch::factory()
        ->for($company)
        ->create(['status' => 'draft']);

    $draftTwo->forceFill([
        'created_at' => $dayOne,
        'updated_at' => $dayOne,
    ])->saveQuietly();

    $processing = PayrollBatch::factory()
        ->for($company)
        ->create(['status' => 'processing']);

    $processing->forceFill([
        'created_at' => $dayTwo,
        'updated_at' => $dayTwo,
    ])->saveQuietly();

    $queued = PayrollBatch::factory()
        ->for($company)
        ->create(['status' => 'queued']);

    $queued->forceFill([
        'created_at' => $dayThree,
        'updated_at' => $dayThree,
    ])->saveQuietly();

    $user = User::factory()->create();

    $response = $this
        ->withServerVariables([
            'HTTP_HOST' => $domain,
            'SERVER_NAME' => $domain,
        ])
        ->actingAs($user)
        ->getJson('http://'.$domain.'/admin/kpi/throughput');

    $response->assertOk();

    $payload = $response->json();

    $labels = $payload['labels'];
    $datasets = collect($payload['datasets'])->keyBy('key');

    $dayOneLabel = $dayOne->toDateString();
    $dayTwoLabel = $dayTwo->toDateString();
    $dayThreeLabel = $dayThree->toDateString();

    $dayOneIndex = array_search($dayOneLabel, $labels, true);
    $dayTwoIndex = array_search($dayTwoLabel, $labels, true);
    $dayThreeIndex = array_search($dayThreeLabel, $labels, true);

    expect($dayOneIndex)->not->toBeFalse();
    expect($dayTwoIndex)->not->toBeFalse();
    expect($dayThreeIndex)->not->toBeFalse();

    expect($datasets['draft']['data'][$dayOneIndex])->toBe(2);
    expect($datasets['processing']['data'][$dayTwoIndex])->toBe(1);
    expect($datasets['queued']['data'][$dayThreeIndex])->toBe(1);
    expect($payload['generated_at'])->toBeString();

    Tenancy::end();
});

it('returns exception flow trend, snapshot, and SLA breaches', function () {
    $tenant = createTenantForTest('kpi-exceptions-'.Str::random(6).'.test');
    $domain = $tenant->domains()->first()->domain;

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();

    expect(Route::has('tenant.kpi.exceptions'))->toBeTrue();

    $dayOne = now()->subDays(2)->setHour(9)->setMinute(0);
    $dayTwo = now()->subDay()->setHour(9)->setMinute(0);
    $dayThree = now()->setHour(9)->setMinute(0);

    $batch = PayrollBatch::factory()
        ->for($company)
        ->create();

    $employee = Employee::factory()
        ->for($company)
        ->create();

    $open = PayrollException::factory()
        ->for($batch)
        ->for($employee)
        ->create([
            'status' => 'open',
            'due_at' => now()->subDay(),
        ]);

    $open->forceFill([
        'created_at' => $dayOne,
        'updated_at' => $dayOne,
    ])->saveQuietly();

    $resolvedPast = PayrollException::factory()
        ->for($batch)
        ->for($employee)
        ->create([
            'status' => 'resolved',
            'due_at' => now()->subDays(2),
            'resolved_at' => $dayTwo,
        ]);

    $resolvedPast->forceFill([
        'created_at' => $dayOne,
        'updated_at' => $dayTwo,
    ])->saveQuietly();

    $inReview = PayrollException::factory()
        ->for($batch)
        ->for($employee)
        ->create([
            'status' => 'in_review',
            'due_at' => now()->addDay(),
        ]);

    $inReview->forceFill([
        'created_at' => $dayTwo,
        'updated_at' => $dayTwo,
    ])->saveQuietly();

    $resolvedToday = PayrollException::factory()
        ->for($batch)
        ->for($employee)
        ->create([
            'status' => 'resolved',
            'due_at' => now()->addDays(2),
            'resolved_at' => $dayThree,
        ]);

    $resolvedToday->forceFill([
        'created_at' => $dayThree,
        'updated_at' => $dayThree,
    ])->saveQuietly();

    $user = User::factory()->create();

    $response = $this
        ->withServerVariables([
            'HTTP_HOST' => $domain,
            'SERVER_NAME' => $domain,
        ])
        ->actingAs($user)
        ->getJson('http://'.$domain.'/admin/kpi/exceptions');

    $response->assertOk();

    $payload = $response->json();

    $labels = $payload['labels'];
    $datasets = collect($payload['datasets'])->keyBy('key');
    $snapshot = collect($payload['status_snapshot'])->keyBy('key');

    $dayOneIndex = array_search($dayOne->toDateString(), $labels, true);
    $dayTwoIndex = array_search($dayTwo->toDateString(), $labels, true);
    $dayThreeIndex = array_search($dayThree->toDateString(), $labels, true);

    expect($dayOneIndex)->not->toBeFalse();
    expect($dayTwoIndex)->not->toBeFalse();
    expect($dayThreeIndex)->not->toBeFalse();

    expect($datasets['opened']['data'][$dayOneIndex])->toBe(2);
    expect($datasets['opened']['data'][$dayTwoIndex])->toBe(1);
    expect($datasets['opened']['data'][$dayThreeIndex])->toBe(1);

    expect($datasets['resolved']['data'][$dayTwoIndex])->toBe(1);
    expect($datasets['resolved']['data'][$dayThreeIndex])->toBe(1);

    expect($snapshot['open']['value'])->toBe(1);
    expect($snapshot['in_review']['value'])->toBe(1);
    expect($snapshot['resolved']['value'])->toBe(2);

    expect($payload['sla_breaches'])->toBe(1);
    expect($payload['generated_at'])->toBeString();

    Tenancy::end();
});

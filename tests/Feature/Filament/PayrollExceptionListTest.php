<?php

use App\Filament\Resources\PayrollExceptionResource;
use App\Filament\Resources\PayrollExceptionResource\Pages\ListPayrollExceptions;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
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

    if (! Schema::hasTable('permissions')) {
        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--path' => 'database/migrations/tenant/2025_09_25_100200_create_permission_tables.php',
            '--force' => true,
        ]);
    }

    putenv('OPS_ADMIN_EMAIL=ops@example.test');
    $_ENV['OPS_ADMIN_EMAIL'] = 'ops@example.test';
});

it('defaults to the open tab and scopes results accordingly', function () {
    $tenant = createTenantForTest('exceptions-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $open = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'due_at' => now()->addDay(),
    ]);

    $inReview = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'in_review',
        'due_at' => now()->addDays(2),
    ]);

    $resolved = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'resolved',
        'due_at' => now()->subDay(),
    ]);

    $user = User::query()->updateOrCreate(
        ['email' => 'ops@example.test'],
        [
            'name' => 'Ops Admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ],
    );

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        $component = Livewire::actingAs($user)->test(ListPayrollExceptions::class);

        $component
            ->assertSet('activeTab', 'open')
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$resolved, $inReview]);
    } finally {
        Tenancy::end();
    }
});

it('filters overdue tab to open and in-review items past due', function () {
    $tenant = createTenantForTest('exceptions-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $overdueOpen = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'due_at' => now()->subDay(),
    ]);

    $overdueReview = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'in_review',
        'due_at' => now()->subDays(2),
    ]);

    $future = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'due_at' => now()->addDays(3),
    ]);

    $resolved = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'resolved',
        'due_at' => now()->subDays(3),
    ]);

    $user = User::query()->updateOrCreate(
        ['email' => 'ops@example.test'],
        [
            'name' => 'Ops Admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ],
    );

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        $component = Livewire::actingAs($user)->test(ListPayrollExceptions::class);

        $component
            ->set('activeTab', 'overdue')
            ->assertCanSeeTableRecords([$overdueOpen, $overdueReview])
            ->assertCanNotSeeTableRecords([$future, $resolved]);
    } finally {
        Tenancy::end();
    }
});

it('updates assignment details from the inspect flyout', function () {
    $tenant = createTenantForTest('exceptions-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $exception = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'assigned_to' => null,
        'due_at' => null,
        'metadata' => [],
    ]);

    $user = User::query()->updateOrCreate(
        ['email' => 'ops@example.test'],
        [
            'name' => 'Ops Admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ],
    );

    $dueAt = now()->addDays(5)->startOfMinute();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        Livewire::actingAs($user)
            ->test(ListPayrollExceptions::class)
            ->callTableAction('inspect', $exception, data: [
                'status' => 'in_review',
                'assigned_to' => 'Aisha Faris',
                'due_at' => $dueAt->format('Y-m-d H:i'),
                'notes' => 'Confirm salary with Mudad.',
            ])
            ->assertNotified();

        $exception->refresh();

        expect($exception)
            ->status->toBe('in_review')
            ->assigned_to->toBe('Aisha Faris')
            ->due_at->not->toBeNull()
            ->resolved_at->toBeNull();

        expect($exception->due_at?->toDateTimeString())
            ->toBe($dueAt->copy()->setSecond(0)->toDateTimeString());

        expect(data_get($exception->metadata, 'notes'))->toBe('Confirm salary with Mudad.');
    } finally {
        Tenancy::end();
    }
});

it('returns activity feed from audit trail data', function () {
    $tenant = createTenantForTest('exceptions-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    try {
        $company = Company::factory()->create();
        $employee = Employee::factory()->for($company)->create();

        $now = now();
        $validationAt = $now->copy()->subMinutes(10);
        $queuedAt = $now->copy()->subMinutes(2);
        $generatedAt = $now->copy();
        $availableAt = $generatedAt->copy()->addMinute();

        $batch = PayrollBatch::factory()
            ->for($company)
            ->create([
                'metadata' => [
                    'audit' => [
                        'trail' => [
                            [
                                'event' => 'validation.run',
                                'occurred_at' => $validationAt->toISOString(),
                                'payload' => [
                                    'summary' => [
                                        'total' => 5,
                                        'failures' => 2,
                                    ],
                                    'rule_sets' => ['uae-wps-v1'],
                                ],
                            ],
                            [
                                'event' => 'sif.queued',
                                'occurred_at' => $queuedAt->toISOString(),
                                'payload' => [
                                    'template' => 'uae-wps-v1',
                                ],
                            ],
                            [
                                'event' => 'sif.generated',
                                'occurred_at' => $generatedAt->toISOString(),
                                'payload' => [
                                    'template' => 'uae-wps-v1',
                                    'available_at' => $availableAt->toISOString(),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $exception = PayrollException::factory()
            ->for($batch)
            ->for($employee)
            ->create();

        $method = new ReflectionMethod(PayrollExceptionResource::class, 'getActivityFeed');
        $method->setAccessible(true);

        $feed = $method->invoke(null, $exception);

        expect($feed)
            ->toHaveCount(3)
            ->sequence(
                fn ($event) => $event->event->toBe('sif.generated'),
                fn ($event) => $event->event->toBe('sif.queued'),
                fn ($event) => $event->event->toBe('validation.run'),
            );

        expect($feed[0]['payload']['template'] ?? null)->toBe('uae-wps-v1');
        expect($feed[0]['payload']['available_at'] ?? null)->toBe($availableAt->toISOString());
        expect($feed[2]['payload']['summary']['failures'] ?? null)->toBe(2);
    } finally {
        Tenancy::end();
    }
});

it('marks an exception as resolved via quick action', function () {
    $tenant = createTenantForTest('exceptions-resolve-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $exception = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'resolved_at' => null,
    ]);

    $user = User::query()->updateOrCreate(
        ['email' => 'ops@example.test'],
        [
            'name' => 'Ops Admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ],
    );

    $now = Carbon::now()->startOfMinute();
    Carbon::setTestNow($now);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        Livewire::actingAs($user)
            ->test(ListPayrollExceptions::class)
            ->callTableAction('markResolved', $exception)
            ->assertNotified();

        $exception->refresh();

        expect($exception)
            ->status->toBe('resolved')
            ->resolved_at->not->toBeNull();

        expect($exception->resolved_at?->toDateTimeString())->toBe($now->toDateTimeString());
    } finally {
        Carbon::setTestNow();
        Tenancy::end();
    }
});

it('clears the resolution timestamp when reopening from the inspect action', function () {
    $tenant = createTenantForTest('exceptions-reopen-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $resolvedAt = now()->subDay()->startOfMinute();

    $exception = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'resolved',
        'resolved_at' => $resolvedAt,
        'assigned_to' => 'Ops Admin',
    ]);

    $user = User::query()->updateOrCreate(
        ['email' => 'ops@example.test'],
        [
            'name' => 'Ops Admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ],
    );

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    try {
        Livewire::actingAs($user)
            ->test(ListPayrollExceptions::class)
            ->set('activeTab', 'resolved')
            ->callTableAction('inspect', $exception, data: [
                'status' => 'open',
                'assigned_to' => 'Ops Admin',
                'due_at' => $resolvedAt->format('Y-m-d H:i'),
                'notes' => '',
            ])
            ->assertNotified();

        $exception->refresh();

        expect($exception)
            ->status->toBe('open')
            ->resolved_at->toBeNull();
    } finally {
        Tenancy::end();
    }
});

it('computes SLA metadata for overdue exceptions', function () {
    $tenant = createTenantForTest('exceptions-sla-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::factory()->create();
    $batch = PayrollBatch::factory()->for($company)->create();
    $employee = Employee::factory()->for($company)->create();

    $overdueAt = Carbon::now()->subMinutes(90);

    $exception = PayrollException::factory()->for($batch)->for($employee)->create([
        'status' => 'open',
        'due_at' => $overdueAt,
    ]);

    try {
        $method = new ReflectionMethod(PayrollExceptionResource::class, 'getSlaMeta');
        $method->setAccessible(true);

        $meta = $method->invoke(null, $exception);

        expect($meta)
            ->color->toBe('danger')
            ->text->toContain(__('exceptions.sla.overdue', ['time' => '']));
    } finally {
        Tenancy::end();
    }
});

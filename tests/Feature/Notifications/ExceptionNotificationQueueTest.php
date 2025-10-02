<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\ExceptionNotification;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

describe('exception notification queue', function () {
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
    });

    it('queues bilingual notifications when status and assignment change', function () {
        $tenant = createTenantForTest('notification-'.Str::random(6).'.test');

        Tenancy::initialize($tenant);

        try {
            $company = Company::factory()->create();
            $batch = PayrollBatch::factory()->for($company)->create([
                'reference' => 'BATCH-001',
            ]);
            $employee = Employee::factory()->for($company)->create([
                'external_id' => 'EMP-0091',
            ]);

            $exception = PayrollException::factory()
                ->for($batch)
                ->for($employee)
                ->create([
                    'status' => 'open',
                    'assigned_to' => null,
                ]);

            $exception->update([
                'status' => 'in_review',
                'assigned_to' => 'Aisha Faris',
            ]);

            $notifications = ExceptionNotification::query()->get();

            expect($notifications)->toHaveCount(4);

            $statusEn = $notifications
                ->first(fn (ExceptionNotification $item) => $item->type === 'status_changed' && $item->locale === 'en');

            expect($statusEn)->not->toBeNull();
            expect($statusEn->title)->toBe('Exception status updated');
            expect($statusEn->body)->toBe('Status changed from Open to In review for EMP-0091 in batch BATCH-001.');

            $assignmentAr = $notifications
                ->first(fn (ExceptionNotification $item) => $item->type === 'assignment_changed' && $item->locale === 'ar');

            expect($assignmentAr)->not->toBeNull();
            expect($assignmentAr->title)->toBe('تحديث مسؤول الاستثناء');
            expect($assignmentAr->body)->toBe('تم تعيين Aisha Faris على الاستثناء للموظف EMP-0091 في الدفعة BATCH-001.');

            $statusPayload = $statusEn->payload;

            expect($statusPayload)->toMatchArray([
                'exception_id' => $exception->getKey(),
                'payroll_batch_id' => $batch->getKey(),
                'employee_id' => $employee->getKey(),
                'status_from' => 'open',
                'status_to' => 'in_review',
            ]);

            $assignmentPayload = $assignmentAr->payload;

            expect($assignmentPayload)->toMatchArray([
                'exception_id' => $exception->getKey(),
                'payroll_batch_id' => $batch->getKey(),
                'employee_id' => $employee->getKey(),
                'assignee_to' => 'Aisha Faris',
            ]);

            expect($assignmentPayload)->not->toHaveKey('assignee_from');
        } finally {
            Tenancy::end();
        }
    });
});

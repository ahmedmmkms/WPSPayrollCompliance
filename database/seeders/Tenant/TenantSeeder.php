<?php

namespace Database\Seeders\Tenant;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate([
            'tenant_id' => tenant('id'),
        ], [
            'name' => tenant('company_name') ?? 'New Tenant',
            'trade_license' => null,
            'contact_email' => tenant('contact_email'),
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $permissions = [
            'employees.view',
            'employees.manage',
            'payroll.view',
            'payroll.manage',
        ];

        foreach ($permissions as $permission) {
            $adminRole->givePermissionTo(
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ])
            );
        }

        Employee::firstOrCreate([
            'company_id' => $company->id,
            'email' => $company->contact_email,
        ], [
            'external_id' => Str::uuid()->toString(),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone' => null,
            'salary' => 0,
            'currency' => 'AED',
        ]);

        if ($company->employees()->count() <= 1) {
            Employee::factory()
                ->count(5)
                ->state(fn () => ['company_id' => $company->id])
                ->create();
        }

        if ($company->payrollBatches()->count() === 0) {
            PayrollBatch::factory()
                ->count(2)
                ->state(fn () => ['company_id' => $company->id, 'status' => 'draft'])
                ->create();
        }
    }
}

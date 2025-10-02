<?php

use App\Events\EmployeesImported;
use App\Jobs\ImportEmployees;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Facades\Tenancy;

it('imports employees from a spreadsheet and dispatches an event', function () {
    $tenant = createTenantForTest('acme.test');

    Event::fake([EmployeesImported::class]);
    Storage::fake('local');

    $company = Company::create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme Corp',
        'contact_email' => 'ops@acme.test',
    ]);

    $csv = implode("\n", [
        'external_id,first_name,last_name,email,phone,salary,currency',
        'EMP001,Jane,Doe,jane@example.test,+971500000000,8000,AED',
    ]);

    $path = 'imports/'.$tenant->id.'/employees.csv';

    Storage::disk('local')->put($path, $csv);

    $job = new ImportEmployees($tenant->id, $path, $company->id);
    $job->handle();

    Tenancy::initialize($tenant);

    expect(Employee::query()->count())->toBe(1)
        ->and(Employee::query()->first()->first_name)->toBe('Jane');

    Tenancy::end();

    Storage::disk('local')->assertMissing($path);

    Event::assertDispatched(EmployeesImported::class, function (EmployeesImported $event) use ($tenant) {
        return $event->tenantId === $tenant->id && $event->imported === 1 && $event->skipped === 0;
    });
});

it('fails when required columns are missing', function () {
    $tenant = createTenantForTest('globex.test');

    Storage::fake('local');

    $csv = implode("\n", [
        'first,last',
        'John,Doe',
    ]);

    $path = 'imports/'.$tenant->id.'/invalid.csv';

    Storage::disk('local')->put($path, $csv);

    $job = new ImportEmployees($tenant->id, $path, null);

    expect(fn () => $job->handle())->toThrow(Exception::class);
});

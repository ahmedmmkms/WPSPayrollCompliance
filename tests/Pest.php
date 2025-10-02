<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

uses(Tests\TestCase::class)->in('Feature');
uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature/Tenancy', 'Feature/Import');

beforeAll(function () {
    if (! Schema::hasTable('tenants')) {
        Artisan::call('migrate', ['--force' => true]);
    }
});

function createTenantForTest(string $domain): Tenant
{
    if (! Schema::hasTable('tenants')) {
        Artisan::call('migrate', ['--force' => true]);
    }

    $tenant = Tenant::create([
        'id' => (string) Str::uuid(),
        'data' => [
            'company_name' => ucfirst(Str::before($domain, '.')),
            'contact_email' => 'ops@'.$domain,
        ],
    ]);

    $tenant->domains()->create(['domain' => $domain]);

    $tenantConnection = config('database.connections.tenant');
    $driver = $tenantConnection['driver'] ?? config('database.default');

    if ($driver === 'sqlite') {
        $tenantDatabasePath = database_path('tenant-'.Str::slug($domain).'.sqlite');

        if (file_exists($tenantDatabasePath)) {
            unlink($tenantDatabasePath);
        }

        touch($tenantDatabasePath);

        config()->set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $tenantDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        Tenancy::initialize($tenant);

        if (! Schema::connection('tenant')->hasTable('employees')) {
            throw new RuntimeException('Tenant employee schema missing for domain: '.$domain);
        }

        Tenancy::end();

        return $tenant;
    }

    $tenant->database()->makeCredentials();

    if ($connection = env('TENANT_DB_TEMPLATE_CONNECTION')) {
        $tenant->setInternal('db_connection', $connection);
        $tenant->save();
    }

    Artisan::call('tenants:migrate', [
        '--tenants' => [$tenant->getTenantKey()],
        '--force' => true,
    ]);

    Tenancy::initialize($tenant);

    if (! Schema::connection('tenant')->hasTable('employees')) {
        throw new RuntimeException('Tenant employee schema missing for domain: '.$domain);
    }

    Tenancy::end();

    return $tenant;
}

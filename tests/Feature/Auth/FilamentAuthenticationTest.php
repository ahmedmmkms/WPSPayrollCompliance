<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Stancl\Tenancy\Database\Models\Domain;

beforeEach(function () {
    $this->withExceptionHandling();

    if (! Schema::hasTable('permissions')) {
        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--path' => 'database/migrations/tenant/2025_09_25_100200_create_permission_tables.php',
            '--force' => true,
        ]);
    }

    if (Schema::hasTable('tenants')) {
        Domain::query()->delete();
        Tenant::query()->delete();
    }
});

it('redirects unauthenticated visitors to the Filament login route', function () {
    createTenantForTest('acme.test');

    $response = $this->get('http://acme.test/admin');

    $response->assertRedirect('http://acme.test/admin/login');
});

it('allows authenticated admins to load the Filament dashboard', function () {
    createTenantForTest('acme.test');

    putenv('OPS_ADMIN_EMAIL=ops@example.test');
    putenv('OPS_ADMIN_PASSWORD=password');
    $_ENV['OPS_ADMIN_EMAIL'] = 'ops@example.test';
    $_ENV['OPS_ADMIN_PASSWORD'] = 'password';

    $user = User::factory()->create([
        'email' => 'ops@example.test',
    ]);

    if (Schema::hasTable('roles')) {
        $role = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);
    }

    $this->actingAs($user, 'web');

    $response = $this->get('http://acme.test/admin');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

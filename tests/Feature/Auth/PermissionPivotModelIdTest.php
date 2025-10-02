<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

dataset('permissionPivotMigrations', [
    'application migration' => 'database/migrations/2025_10_01_035901_update_permission_pivot_model_ids.php',
    'tenant migration' => 'database/migrations/tenant/2025_09_30_202705_update_model_has_permission_model_ids.php',
]);

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', database_path('testing.sqlite'));
    config()->set('database.connections.sqlite.url', null);
    config()->set('cache.default', 'array');
    config()->set('permission.cache.store', 'array');
    config()->set('queue.default', 'sync');
    config()->set('session.driver', 'array');

    DB::purge('sqlite');
    DB::purge('pgsql');
    DB::setDefaultConnection('sqlite');

    if (! file_exists(database_path('testing.sqlite'))) {
        touch(database_path('testing.sqlite'));
    }

    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
});

it('converts permission pivot model ids to integers and supports role checks', function (string $migrationPath) {
    Schema::dropIfExists('model_has_permissions');
    Schema::dropIfExists('model_has_roles');
    Schema::dropIfExists('permissions');
    Schema::dropIfExists('roles');

    Schema::create('permissions', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    Schema::create('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->string('model_type');
        $table->uuid('model_id');
    });

    Schema::create('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->uuid('model_id');
    });

    $migration = require base_path($migrationPath);
    $migration->up();

    $permissionModelIdColumn = collect(DB::select("PRAGMA table_info('model_has_permissions')"))
        ->firstWhere('name', 'model_id');

    $roleModelIdColumn = collect(DB::select("PRAGMA table_info('model_has_roles')"))
        ->firstWhere('name', 'model_id');

    expect(strtolower($permissionModelIdColumn->type))->toBe('integer');
    expect(strtolower($roleModelIdColumn->type))->toBe('integer');

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();

    $role = Role::query()->firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    expect(fn () => $user->assignRole($role))->not->toThrow(QueryException::class);
    expect($user->fresh()->hasRole('admin'))->toBeTrue();
})->with('permissionPivotMigrations');

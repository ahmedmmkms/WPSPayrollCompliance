<?php

namespace Database\Seeders\Central;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OperationsAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('OPS_ADMIN_EMAIL');

        if (! $email) {
            return;
        }

        $name = env('OPS_ADMIN_NAME', 'Operations Admin');
        $password = env('OPS_ADMIN_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password ?: Str::random(32)),
            ],
        );

        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleName = config('filament-shield.super_admin', 'Super Admin');

        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        rescue(fn () => $user->assignRole($role), report: false);
    }
}

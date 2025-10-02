<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $opsEmail = env('OPS_ADMIN_EMAIL');

        if ($opsEmail && $this->email === $opsEmail) {
            return true;
        }

        $rolesTable = config('permission.table_names.roles', 'roles');

        if (Schema::hasTable($rolesTable)) {
            try {
                if ($this->hasRole('admin') || $this->hasRole(config('filament-shield.super_admin', 'Super Admin'))) {
                    return true;
                }
            } catch (QueryException $exception) {
                report($exception);
            }
        }

        return false;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Employee extends Model
{
    use HasFactory;
    use HasUuids;
    use TenantConnection;

    protected $fillable = [
        'company_id',
        'external_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'salary',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'metadata' => 'encrypted:array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(PayrollException::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class PayrollBatch extends Model
{
    use HasFactory;
    use HasUuids;
    use TenantConnection;

    protected $fillable = [
        'company_id',
        'reference',
        'scheduled_for',
        'status',
        'metadata',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
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

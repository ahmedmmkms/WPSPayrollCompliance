<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'trade_license',
        'contact_email',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'encrypted:array',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollBatches(): HasMany
    {
        return $this->hasMany(PayrollBatch::class);
    }
}

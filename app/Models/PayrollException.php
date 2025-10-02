<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class PayrollException extends Model
{
    /** @use HasFactory<\Database\Factories\PayrollExceptionFactory> */
    use HasFactory;

    use HasUuids;
    use TenantConnection;

    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        'rule_id',
        'rule_set_id',
        'severity',
        'status',
        'origin',
        'assigned_to',
        'due_at',
        'resolved_at',
        'message',
        'context',
        'metadata',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'message' => 'encrypted:array',
        'context' => 'encrypted:array',
        'metadata' => 'encrypted:array',
    ];

    public function payrollBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

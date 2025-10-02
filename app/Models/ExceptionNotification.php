<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class ExceptionNotification extends Model
{
    /** @use HasFactory<\Database\Factories\ExceptionNotificationFactory> */
    use HasFactory;

    use HasUuids;
    use TenantConnection;

    protected $fillable = [
        'payroll_exception_id',
        'type',
        'locale',
        'title',
        'body',
        'payload',
        'queued_at',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function payrollException(): BelongsTo
    {
        return $this->belongsTo(PayrollException::class);
    }
}

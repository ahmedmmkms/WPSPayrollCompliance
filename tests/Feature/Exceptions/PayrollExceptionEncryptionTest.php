<?php

use App\Models\Company;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

it('persists payroll exception payload fields encrypted at rest', function (): void {
    $tenant = createTenantForTest('encryption-'.Str::random(6).'.test');

    Tenancy::initialize($tenant);

    $company = Company::create([
        'tenant_id' => $tenant->id,
        'name' => 'Acme FZ-LLC',
        'trade_license' => 'TL-12345',
        'contact_email' => 'ops@acme.test',
    ]);

    $batch = PayrollBatch::create([
        'company_id' => $company->id,
        'reference' => strtoupper(Str::random(12)),
        'scheduled_for' => now()->addDay(),
        'status' => 'draft',
        'metadata' => [],
    ]);

    $message = ['en' => 'Sensitive salary mismatch'];
    $context = ['field' => 'salary', 'submitted' => 1000, 'expected' => 1200];
    $metadata = ['reviewed_by' => 'ops@example.test'];

    $exception = PayrollException::create([
        'payroll_batch_id' => $batch->id,
        'employee_id' => null,
        'rule_id' => 'rule-1001',
        'rule_set_id' => 'uae-wps-v1',
        'severity' => 'error',
        'status' => 'open',
        'origin' => 'validation',
        'assigned_to' => null,
        'due_at' => now()->addDays(2),
        'resolved_at' => null,
        'message' => $message,
        'context' => $context,
        'metadata' => $metadata,
    ]);

    $raw = DB::connection('tenant')
        ->table('payroll_exceptions')
        ->where('id', $exception->id)
        ->first();

    expect($raw->message)->toBeString()
        ->and($raw->message)->not->toContain('Sensitive salary mismatch');

    expect($raw->context)->toBeString()
        ->and($raw->context)->not->toContain('salary');

    expect($raw->metadata)->toBeString()
        ->and($raw->metadata)->not->toContain('reviewed_by');

    $reloaded = PayrollException::query()->find($exception->id);

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->message)->toEqual($message)
        ->and($reloaded->context)->toEqual($context)
        ->and($reloaded->metadata)->toEqual($metadata);

    Tenancy::end();
});

<?php

use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Support\Validation\RuleRepository;
use App\Support\Validation\ValidationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

it('returns no failures for a compliant UAE WPS batch', function () {
    $companyId = (string) Str::uuid();

    $batch = PayrollBatch::make([
        'company_id' => $companyId,
        'reference' => 'BATCH-ACME-001',
        'metadata' => [],
    ]);
    $batch->id = (string) Str::uuid();

    $employees = new Collection([
        tap(Employee::make([
            'company_id' => $companyId,
            'external_id' => 'EMP001',
            'salary' => 7500,
            'currency' => 'AED',
        ]), static fn (Employee $employee) => $employee->id = (string) Str::uuid()),
        tap(Employee::make([
            'company_id' => $companyId,
            'external_id' => 'EMP002',
            'salary' => 9200,
            'currency' => 'AED',
        ]), static fn (Employee $employee) => $employee->id = (string) Str::uuid()),
    ]);

    $service = new ValidationService(RuleRepository::fromConfig());

    $report = $service->run($batch, $employees, ['uae-wps-v1']);

    expect($report->failures())->toHaveCount(0)
        ->and($report->results())->toHaveCount(2 * 2 + 2); // two employee rules per employee + two batch rules
});

it('flags failures for UAE WPS when data is invalid', function () {
    $companyId = (string) Str::uuid();

    $batch = PayrollBatch::make([
        'company_id' => $companyId,
        'reference' => '',
        'metadata' => [],
    ]);
    $batch->id = (string) Str::uuid();

    $employees = new Collection([
        tap(Employee::make([
            'company_id' => $companyId,
            'external_id' => 'EMP100',
            'salary' => -50,
            'currency' => 'USD',
        ]), static fn (Employee $employee) => $employee->id = (string) Str::uuid()),
    ]);

    $service = new ValidationService(RuleRepository::fromConfig());

    $report = $service->run($batch, $employees, ['uae-wps-v1']);

    expect($report->failures())->toHaveCount(3)
        ->and($report->failures()->pluck('ruleId')->all())
        ->toEqualCanonicalizing([
            'uae-wps-batch-reference',
            'uae-wps-salary-minimum',
            'uae-wps-currency',
        ]);
});

it('returns no failures for a compliant KSA Mudad batch', function () {
    $companyId = (string) Str::uuid();

    $batch = PayrollBatch::make([
        'company_id' => $companyId,
        'reference' => 'MUDAD-REF-001',
        'metadata' => [
            'mudad' => [
                'portal_reference' => 'PRT-001',
            ],
        ],
    ]);
    $batch->id = (string) Str::uuid();

    $employees = new Collection([
        tap(Employee::make([
            'company_id' => $companyId,
            'external_id' => 'EMP-KSA-1',
            'salary' => 6500,
            'currency' => 'SAR',
            'metadata' => [
                'mudad' => [
                    'national_id' => '1234567890',
                    'contract_type' => 'full_time',
                    'bank_iban' => 'SA'.str_repeat('1', 22),
                ],
            ],
        ]), static fn (Employee $employee) => $employee->id = (string) Str::uuid()),
    ]);

    $service = new ValidationService(RuleRepository::fromConfig());

    $report = $service->run($batch, $employees, ['ksa-mudad-sandbox']);

    expect($report->failures())->toHaveCount(0)
        ->and($report->results())->not()->toBeEmpty();
});

it('flags failures for KSA Mudad when identifiers are invalid', function () {
    $companyId = (string) Str::uuid();

    $batch = PayrollBatch::make([
        'company_id' => $companyId,
        'reference' => 'MUDAD-REF-002',
        'metadata' => [
            'mudad' => [
                'portal_reference' => null,
            ],
        ],
    ]);
    $batch->id = (string) Str::uuid();

    $employees = new Collection([
        tap(Employee::make([
            'company_id' => $companyId,
            'external_id' => null,
            'salary' => 5000,
            'currency' => 'USD',
            'metadata' => [
                'mudad' => [
                    'national_id' => 'ABC123',
                    'contract_type' => 'invalid',
                    'bank_iban' => 'SA123',
                ],
            ],
        ]), static fn (Employee $employee) => $employee->id = (string) Str::uuid()),
    ]);

    $service = new ValidationService(RuleRepository::fromConfig());

    $report = $service->run($batch, $employees, ['ksa-mudad-sandbox']);

    expect($report->failures()->pluck('ruleId'))
        ->toContain('ksa-mudad-basic-id')
        ->toContain('ksa-mudad-currency')
        ->toContain('ksa-mudad-national-id')
        ->toContain('ksa-mudad-iban-format')
        ->toContain('ksa-mudad-contract-type')
        ->toContain('ksa-mudad-portal-reference');
});

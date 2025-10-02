<?php

namespace App\Support\Validation;

use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Support\Audit\AuditTrailRecorder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;

class BatchValidationManager
{
    public function __construct(
        private readonly ValidationService $service,
        private readonly ValidationExceptionSynchronizer $exceptionSynchronizer,
        private readonly AuditTrailRecorder $auditTrail,
    ) {}

    /**
     * @param  array<int, string>|null  $ruleSetIdentifiers
     */
    public function run(PayrollBatch $batch, ?array $ruleSetIdentifiers = null): ValidationReport
    {
        $employees = Employee::query()
            ->where('company_id', $batch->company_id)
            ->get();

        if ($employees->isEmpty()) {
            throw new RuntimeException('Add employees to this batch before running validation.');
        }

        $report = $this->service->run(
            batch: $batch,
            employees: $employees,
            ruleSetIdentifiers: $ruleSetIdentifiers,
        );

        $metadata = $this->prepareMetadata($batch, $report);
        $metadata = $this->auditTrail->append($metadata, 'validation.run', [
            'rule_sets' => $report->ruleSetIds,
            'summary' => [
                'total' => $report->results()->count(),
                'failures' => $report->failures()->count(),
            ],
        ]);

        $batch->update([
            'metadata' => $metadata,
        ]);

        $this->exceptionSynchronizer->sync($batch, $report);

        return $report;
    }

    private function prepareMetadata(PayrollBatch $batch, ValidationReport $report): array
    {
        $existing = $batch->metadata ?? [];

        if (! is_array($existing)) {
            $existing = (array) $existing;
        }

        $summary = [
            'status' => $report->failures()->isEmpty() ? 'passed' : 'failed',
            'ran_at' => Carbon::now()->toISOString(),
            'rule_sets' => $report->ruleSetIds,
            'results' => $report->results()
                ->map(static fn (ValidationResult $result) => $result->toArray())
                ->all(),
            'summary' => [
                'total' => $report->results()->count(),
                'passes' => $report->passes()->count(),
                'failures' => $report->failures()->count(),
            ],
        ];

        return Arr::set($existing, 'validation', $summary);
    }
}

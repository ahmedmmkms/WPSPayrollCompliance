<?php

namespace App\Support\Validation;

use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ValidationExceptionSynchronizer
{
    public function sync(PayrollBatch $batch, ValidationReport $report): void
    {
        $results = $report->results();

        $failures = $results
            ->filter(fn (ValidationResult $result) => ! $result->passed)
            ->groupBy(fn (ValidationResult $result) => $this->exceptionKey($batch, $result));

        foreach ($failures as $key => $group) {
            /** @var ValidationResult $result */
            $result = $group->first();

            $exception = PayrollException::query()->firstOrNew([
                'payroll_batch_id' => $batch->getKey(),
                'rule_id' => $result->ruleId,
                'employee_id' => $this->resolveEmployeeId($result),
            ]);

            $metadata = array_merge($exception->metadata ?? [], $this->buildMetadata($result));

            $exception->fill([
                'rule_set_id' => $result->ruleSetId,
                'severity' => $result->severity,
                'status' => 'open',
                'origin' => 'validation',
                'message' => $result->message,
                'context' => $result->context,
                'metadata' => $metadata,
                'due_at' => $this->resolveDueAt($result),
                'resolved_at' => null,
            ]);

            $exception->save();
        }

        $this->resolveClearedExceptions($batch, $results);
    }

    private function resolveClearedExceptions(PayrollBatch $batch, Collection $results): void
    {
        $passes = $results
            ->filter(fn (ValidationResult $result) => $result->passed)
            ->map(fn (ValidationResult $result) => [
                'rule_id' => $result->ruleId,
                'employee_id' => $this->resolveEmployeeId($result),
            ]);

        if ($passes->isEmpty()) {
            return;
        }

        foreach ($passes as $attributes) {
            $exception = PayrollException::query()->where([
                'payroll_batch_id' => $batch->getKey(),
                'rule_id' => $attributes['rule_id'],
                'employee_id' => $attributes['employee_id'],
            ])->first();

            if (! $exception) {
                continue;
            }

            if ($exception->status !== 'resolved') {
                $exception->update([
                    'status' => 'resolved',
                    'resolved_at' => Carbon::now(),
                ]);
            }
        }
    }

    private function exceptionKey(PayrollBatch $batch, ValidationResult $result): string
    {
        $parts = [
            $batch->getKey(),
            $result->ruleId,
            $this->resolveEmployeeId($result) ?? 'batch',
        ];

        return implode(':', $parts);
    }

    private function resolveEmployeeId(ValidationResult $result): ?string
    {
        $employeeId = Arr::get($result->context, 'employee_id');

        return $employeeId ? (string) $employeeId : null;
    }

    private function buildMetadata(ValidationResult $result): array
    {
        return [
            'value' => Arr::get($result->context, 'value'),
            'target' => $result->target,
            'last_triggered_at' => Carbon::now()->toISOString(),
        ];
    }

    private function resolveDueAt(ValidationResult $result): ?CarbonInterface
    {
        return match ($result->severity) {
            'warning' => Carbon::now()->addDays(3),
            default => Carbon::now()->addDay(),
        };
    }
}

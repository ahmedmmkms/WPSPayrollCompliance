<?php

namespace App\Support\Validation;

use App\Models\Employee;
use App\Models\PayrollBatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Stringable;

class ValidationService
{
    public function __construct(
        private readonly RuleRepository $repository,
    ) {}

    /**
     * @param  array<int, string>|null  $ruleSetIdentifiers
     */
    public function run(PayrollBatch $batch, Collection $employees, ?array $ruleSetIdentifiers = null): ValidationReport
    {
        $identifiers = $ruleSetIdentifiers ?? config('validation.default_sets', []);

        if (empty($identifiers)) {
            throw new InvalidArgumentException('At least one rule set must be specified.');
        }

        $ruleSets = collect($identifiers)
            ->map(fn (string $identifier) => $this->repository->find($identifier));

        $results = [];

        $batchPayload = $this->buildBatchPayload($batch, $employees);

        foreach ($ruleSets as $ruleSet) {
            foreach ($ruleSet->rules as $rule) {
                if ($rule->target === 'batch') {
                    $results[] = $this->evaluateRule(
                        ruleSet: $ruleSet,
                        rule: $rule,
                        value: $batchPayload,
                        context: [
                            'scope' => 'batch',
                            'batch_id' => $batch->getKey(),
                        ],
                        employees: $employees,
                    );

                    continue;
                }

                if ($rule->target === 'employee') {
                    foreach ($employees as $employee) {
                        $results[] = $this->evaluateRule(
                            ruleSet: $ruleSet,
                            rule: $rule,
                            value: $employee,
                            context: [
                                'scope' => 'employee',
                                'employee_id' => $employee->getKey(),
                                'external_id' => $employee->external_id,
                            ],
                            employees: $employees,
                        );
                    }

                    continue;
                }

                Log::warning('Skipping unsupported rule target', [
                    'rule_id' => $rule->id,
                    'target' => $rule->target,
                ]);
            }
        }

        return new ValidationReport(
            batchId: $batch->getKey(),
            ruleSetIds: $identifiers,
            results: $results,
        );
    }

    private function buildBatchPayload(PayrollBatch $batch, Collection $employees): array
    {
        return array_merge($batch->toArray(), [
            'employee_count' => $employees->count(),
        ]);
    }

    private function evaluateRule(RuleSet $ruleSet, RuleDefinition $rule, mixed $value, array $context, Collection $employees): ValidationResult
    {
        $subjectValue = $this->resolveValue($rule, $value, $employees);
        $passed = $this->passes($rule, $subjectValue);

        return new ValidationResult(
            ruleId: $rule->id,
            ruleSetId: $ruleSet->id,
            target: $rule->target,
            field: $rule->field,
            severity: $rule->severity,
            passed: $passed,
            message: $rule->message,
            context: array_merge($context, [
                'value' => $subjectValue,
            ]),
        );
    }

    private function resolveValue(RuleDefinition $rule, mixed $entity, Collection $employees): mixed
    {
        if (! $rule->field) {
            return null;
        }

        if ($rule->target === 'batch' && $rule->field === 'employee_count') {
            return $employees->count();
        }

        if (is_array($entity)) {
            return Arr::get($entity, $rule->field);
        }

        if ($entity instanceof Employee || $entity instanceof PayrollBatch) {
            return data_get($entity, $rule->field);
        }

        return data_get($entity, $rule->field);
    }

    private function passes(RuleDefinition $rule, mixed $value): bool
    {
        return match ($rule->type) {
            'required' => $this->evaluateRequired($value),
            'numeric_min' => $this->evaluateNumericMin($value, $rule->options['value'] ?? null),
            'enum' => $this->evaluateEnum($value, $rule->options['allowed'] ?? []),
            'regex' => $this->evaluateRegex($value, $rule->options['pattern'] ?? null),
            default => true,
        };
    }

    private function evaluateRequired(mixed $value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    private function evaluateNumericMin(mixed $value, mixed $threshold): bool
    {
        if ($threshold === null) {
            return true;
        }

        if (! is_numeric($value)) {
            return false;
        }

        return (float) $value >= (float) $threshold;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function evaluateEnum(mixed $value, array $allowed): bool
    {
        if (empty($allowed)) {
            return true;
        }

        if ($value === null) {
            return false;
        }

        return in_array((string) $value, $allowed, true);
    }

    private function evaluateRegex(mixed $value, ?string $pattern): bool
    {
        if ($pattern === null || $pattern === '') {
            return true;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $expression = '~'.$pattern.'~u';

        return preg_match($expression, (string) $value) === 1;
    }
}

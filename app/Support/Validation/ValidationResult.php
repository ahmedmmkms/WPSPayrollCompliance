<?php

namespace App\Support\Validation;

class ValidationResult
{
    /**
     * @param  array<string, string>  $message
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $ruleId,
        public readonly string $ruleSetId,
        public readonly string $target,
        public readonly ?string $field,
        public readonly string $severity,
        public readonly bool $passed,
        public readonly array $message,
        public readonly array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule_id' => $this->ruleId,
            'rule_set_id' => $this->ruleSetId,
            'target' => $this->target,
            'field' => $this->field,
            'severity' => $this->severity,
            'passed' => $this->passed,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}

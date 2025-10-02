<?php

namespace App\Support\Validation;

use Illuminate\Support\Collection;

class ValidationReport
{
    /**
     * @param  array<int, string>  $ruleSetIds
     * @param  array<int, ValidationResult>  $results
     */
    public function __construct(
        public readonly string $batchId,
        public readonly array $ruleSetIds,
        public readonly array $results,
    ) {}

    public function results(): Collection
    {
        return collect($this->results);
    }

    public function failures(): Collection
    {
        return $this->results()
            ->filter(static fn (ValidationResult $result) => ! $result->passed);
    }

    public function passes(): Collection
    {
        return $this->results()
            ->filter(static fn (ValidationResult $result) => $result->passed);
    }
}

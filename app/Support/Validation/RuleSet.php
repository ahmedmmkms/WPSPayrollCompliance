<?php

namespace App\Support\Validation;

use Illuminate\Support\Collection;

class RuleSet
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>|null  $description
     * @param  array<string, mixed>  $metadata
     * @param  array<int, RuleDefinition>  $rules
     */
    public function __construct(
        public readonly string $id,
        public readonly string $version,
        public readonly array $name,
        public readonly ?array $description,
        public readonly array $metadata,
        public readonly array $rules,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $rules = collect($attributes['rules'] ?? [])
            ->map(static fn (array $rule) => RuleDefinition::fromArray($rule))
            ->values()
            ->all();

        $description = $attributes['description'] ?? null;

        if ($description !== null && ! is_array($description)) {
            $description = null;
        }

        return new self(
            id: (string) ($attributes['id'] ?? ''),
            version: (string) ($attributes['version'] ?? '1.0.0'),
            name: is_array($attributes['name'] ?? null) ? $attributes['name'] : [],
            description: $description,
            metadata: is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [],
            rules: $rules,
        );
    }

    /**
     * @return Collection<int, RuleDefinition>
     */
    public function rules(): Collection
    {
        return collect($this->rules);
    }
}

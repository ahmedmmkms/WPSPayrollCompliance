<?php

namespace App\Support\Validation;

class RuleDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $target,
        public readonly ?string $field,
        public readonly string $severity,
        /**
         * @var array<string, string>
         */
        public readonly array $message,
        /**
         * @var array<string, mixed>
         */
        public readonly array $options = [],
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $message = $attributes['message'] ?? [];

        if (! is_array($message)) {
            $message = [];
        }

        $field = $attributes['field'] ?? null;

        if ($field !== null) {
            $field = (string) $field;
        }

        $options = collect($attributes)
            ->except(['id', 'type', 'target', 'field', 'severity', 'message'])
            ->all();

        return new self(
            id: (string) ($attributes['id'] ?? ''),
            type: (string) ($attributes['type'] ?? ''),
            target: (string) ($attributes['target'] ?? ''),
            field: $field,
            severity: (string) ($attributes['severity'] ?? 'error'),
            message: $message,
            options: $options,
        );
    }
}

<?php

namespace App\Support\Sif;

class SifTemplate
{
    /**
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $metadata
     * @param  array<string, array<int, string>>  $structure
     */
    public function __construct(
        public readonly string $key,
        public readonly string $version,
        public readonly array $labels,
        public readonly array $metadata,
        public readonly array $structure,
        public readonly string $filename,
        public readonly string $delimiter = '|',
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            key: (string) ($attributes['key'] ?? ''),
            version: (string) ($attributes['version'] ?? '1.0.0'),
            labels: is_array($attributes['labels'] ?? null) ? $attributes['labels'] : [],
            metadata: is_array($attributes['metadata'] ?? null) ? $attributes['metadata'] : [],
            structure: is_array($attributes['structure'] ?? null) ? $attributes['structure'] : [],
            filename: (string) ($attributes['filename'] ?? 'sif-export.txt'),
            delimiter: (string) ($attributes['delimiter'] ?? '|'),
        );
    }

    /**
     * @return array<int, string>
     */
    public function headerFields(): array
    {
        return $this->structure['header'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function detailFields(): array
    {
        return $this->structure['detail'] ?? [];
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class EmployeeImport
{
    public const REQUIRED_HEADERS = [
        'first_name',
        'last_name',
        'salary',
        'currency',
    ];

    /**
     * Normalize a header row into snake_case keys.
     */
    public static function normalizeHeaders(array $header): array
    {
        return collect($header)
            ->map(fn ($value) => Str::snake(trim((string) $value)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Ensure the normalized header includes all required columns.
     *
     * @throws RuntimeException
     */
    public static function assertHasRequiredHeaders(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);

        if (! empty($missing)) {
            throw new RuntimeException('Missing required columns: '.implode(', ', $missing));
        }
    }

    /**
     * Combine a row of values with the normalized headers.
     */
    public static function mapRow(array $headers, array $values): array
    {
        $trimmed = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $values);

        return Arr::only(array_combine($headers, $trimmed) ?: [], array_merge($headers, ['company_id']));
    }
}

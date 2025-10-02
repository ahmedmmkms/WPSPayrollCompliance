<?php

namespace App\Support\Sif;

use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SifGenerator
{
    /**
     * @param  array<string, mixed>  $batch
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $employees
     */
    public function generate(SifTemplate $template, array $batch, Collection|array $employees): GeneratedSif
    {
        $employeeCollection = $employees instanceof Collection ? $employees : collect($employees);

        if (! array_key_exists('employee_count', $batch)) {
            $batch['employee_count'] = $employeeCollection->count();
        }

        $context = [
            'batch' => $batch,
            'template' => [
                'key' => $template->key,
                'version' => $template->version,
            ],
        ];

        $lines = [];

        if ($template->headerFields()) {
            $lines[] = $this->composeLine($template, $template->headerFields(), $context, null);
        }

        foreach ($employeeCollection as $employee) {
            $lines[] = $this->composeLine($template, $template->detailFields(), $context, $employee);
        }

        $contents = implode(PHP_EOL, $lines).PHP_EOL;

        $filename = $this->interpolateString($template->filename, $context, null);

        return new GeneratedSif(
            filename: $filename,
            contents: $contents,
        );
    }

    /**
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $employee
     */
    private function composeLine(SifTemplate $template, array $fields, array $context, ?array $employee): string
    {
        return collect($fields)
            ->map(fn (string $field) => $this->interpolateString($field, $context, $employee))
            ->implode($template->delimiter);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $employee
     */
    private function interpolateString(string $value, array $context, ?array $employee): string
    {
        if (! str_contains($value, '{{')) {
            return $value;
        }

        $data = $context;

        if ($employee) {
            $data['employee'] = $employee;
        }

        return preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) use ($data) {
            $expression = $matches[1];

            return $this->resolveExpression($expression, $data);
        }, $value) ?? '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveExpression(string $expression, array $data): string
    {
        $parts = explode('|', $expression);
        $path = array_shift($parts);

        $value = Arr::get($data, $path ?? '') ?? '';

        foreach ($parts as $modifier) {
            $modifier = trim($modifier);

            if ($modifier === '') {
                continue;
            }

            $value = $this->applyModifier($modifier, $value);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function applyModifier(string $modifier, mixed $value): mixed
    {
        [$name, $argument] = array_pad(explode(':', $modifier, 2), 2, null);

        return match ($name) {
            'date' => $this->formatDate($value, $argument ?? 'Y-m-d'),
            'number_format' => $this->formatNumber($value, $argument ?? '2'),
            'upper' => is_string($value) ? strtoupper($value) : $value,
            'lower' => is_string($value) ? strtolower($value) : $value,
            default => $value,
        };
    }

    private function formatDate(mixed $value, string $format): string
    {
        if ($value instanceof CarbonInterface) {
            return $value->format($format);
        }

        if (is_string($value)) {
            $timestamp = strtotime($value);

            if ($timestamp !== false) {
                return date($format, $timestamp);
            }
        }

        return '';
    }

    private function formatNumber(mixed $value, string $precision): string
    {
        $decimals = (int) $precision;

        if (! is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, $decimals, '.', '');
    }
}

<?php

namespace App\Support\Metrics;

use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TenantKpiMetrics
{
    public function __construct(
        private ?CarbonImmutable $now = null,
    ) {
        $this->now ??= now()->toImmutable();
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array{key: string, label: string, data: array<int, int>}>, generated_at: string}
     */
    public function throughputTrend(int $days = 14): array
    {
        $end = $this->now->endOfDay();
        $start = $this->now->subDays(max($days - 1, 0))->startOfDay();

        $labels = $this->dateLabels($start, $end);

        $raw = PayrollBatch::query()
            ->selectRaw('DATE(updated_at) as day, status, COUNT(*) as total')
            ->whereBetween('updated_at', [$start, $end])
            ->groupByRaw('DATE(updated_at), status')
            ->orderByRaw('DATE(updated_at) asc')
            ->get();

        $statuses = $raw->pluck('status')->unique()->values();

        if ($statuses->isEmpty()) {
            $statuses = collect(['draft', 'queued', 'processing', 'completed']);
        }

        $datasets = $statuses
            ->mapWithKeys(fn (string $status) => [$status => array_fill(0, count($labels), 0)])
            ->all();

        foreach ($raw as $row) {
            $index = array_search($row->day, $labels, true);

            if ($index === false) {
                continue;
            }

            $status = (string) $row->status;

            if (! array_key_exists($status, $datasets)) {
                $datasets[$status] = array_fill(0, count($labels), 0);
            }

            $datasets[$status][$index] = (int) $row->total;
        }

        $normalized = collect($datasets)
            ->map(function (array $data, string $status): array {
                return [
                    'key' => $status,
                    'label' => $this->batchStatusLabel($status),
                    'data' => array_map('intval', $data),
                ];
            })
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'datasets' => $normalized,
            'generated_at' => $this->now->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     datasets: array<int, array{key: string, label: string, data: array<int, int>}>,
     *     status_snapshot: array<int, array{key: string, label: string, value: int}>,
     *     sla_breaches: int,
     *     generated_at: string
     * }
     */
    public function exceptionFlowTrend(int $days = 14): array
    {
        $end = $this->now->endOfDay();
        $start = $this->now->subDays(max($days - 1, 0))->startOfDay();

        $labels = $this->dateLabels($start, $end);

        $created = PayrollException::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day');

        $resolved = PayrollException::query()
            ->selectRaw('DATE(resolved_at) as day, COUNT(*) as total')
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$start, $end])
            ->groupByRaw('DATE(resolved_at)')
            ->pluck('total', 'day');

        $openedSeries = [];
        $resolvedSeries = [];

        foreach ($labels as $label) {
            $openedSeries[] = (int) ($created[$label] ?? 0);
            $resolvedSeries[] = (int) ($resolved[$label] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'key' => 'opened',
                    'label' => $this->exceptionDatasetLabel('opened'),
                    'data' => $openedSeries,
                ],
                [
                    'key' => 'resolved',
                    'label' => $this->exceptionDatasetLabel('resolved'),
                    'data' => $resolvedSeries,
                ],
            ],
            'status_snapshot' => $this->exceptionStatusSnapshot(),
            'sla_breaches' => $this->slaBreachCount(),
            'generated_at' => $this->now->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, value: int}>
     */
    public function exceptionStatusSnapshot(): array
    {
        $counts = PayrollException::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();

        $statuses = ['open', 'in_review', 'resolved'];

        $snapshot = [];

        foreach ($statuses as $status) {
            $snapshot[] = [
                'key' => $status,
                'label' => $this->exceptionStatusLabel($status),
                'value' => (int) Arr::get($counts, $status, 0),
            ];
        }

        $additional = collect($counts)
            ->reject(fn (int $value, string $status) => in_array($status, $statuses, true))
            ->map(fn (int $value, string $status) => [
                'key' => $status,
                'label' => $this->exceptionStatusLabel($status),
                'value' => $value,
            ])
            ->values()
            ->all();

        return array_merge($snapshot, $additional);
    }

    public function slaBreachCount(): int
    {
        return PayrollException::query()
            ->whereIn('status', ['open', 'in_review'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $this->now)
            ->count();
    }

    /**
     * @return array<int, string>
     */
    protected function dateLabels(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($end->lessThan($start)) {
            return [];
        }

        $days = $start->diffInDays($end) + 1;

        return collect(range(0, $days - 1))
            ->map(fn (int $offset) => $start->addDays($offset)->toDateString())
            ->all();
    }

    protected function batchStatusLabel(string $status): string
    {
        $key = 'metrics.batches.statuses.'.$status;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return Str::title(str_replace('_', ' ', $status));
    }

    protected function exceptionStatusLabel(string $status): string
    {
        $key = 'exceptions.statuses.'.$status;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return Str::title(str_replace('_', ' ', $status));
    }

    protected function exceptionDatasetLabel(string $key): string
    {
        $translationKey = 'metrics.exceptions.datasets.'.$key;
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return Str::title(str_replace('_', ' ', $key));
    }
}

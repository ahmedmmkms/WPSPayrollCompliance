<?php

namespace App\Console\Commands;

use App\Jobs\ImportEmployees;
use App\Jobs\RunBatchValidation;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class QueueBaseline extends Command
{
    protected $signature = 'queue:baseline
        {tenant : Tenant ID or domain to benchmark}
        {--employees=250 : Employees per import run}
        {--runs=3 : Number of iterations per workload}
        {--rule-sets=* : Validation rule sets (defaults to config validation.default_sets)}
        {--output=metrics/queue-baseline.json : Storage path for JSON results}
        {--skip-importer : Skip importer benchmark}
        {--skip-validation : Skip validation benchmark}';

    protected $description = 'Generate importer and validation queue baseline metrics for a tenant environment.';

    public function handle(): int
    {
        $tenant = $this->resolveTenant((string) $this->argument('tenant'));

        if (! $tenant) {
            $this->error('Tenant not found. Provide a valid tenant ID or domain.');

            return self::FAILURE;
        }

        $employees = max((int) $this->option('employees'), 1);
        $runs = max((int) $this->option('runs'), 1);
        $ruleSets = $this->resolveRuleSets();

        $company = Company::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'trade_license' => 'QUEUE-BASELINE',
            ],
            [
                'name' => 'Queue Baseline Co',
                'contact_email' => 'ops@baseline.test',
            ]
        );

        $results = [
            'generated_at' => now()->toIso8601String(),
            'queue_connection' => config('queue.default'),
            'tenant_id' => $tenant->id,
            'employees_per_run' => $employees,
            'runs' => $runs,
        ];

        if (! $this->option('skip-importer')) {
            $results['importer'] = $this->benchmarkImporter($tenant, $company->id, $employees, $runs);
            $this->renderDurationsTable('Importer Job Durations (ms)', $results['importer']['durations_ms']);
            $this->renderSummary('Importer Summary', $results['importer']);
        }

        if (! $this->option('skip-validation')) {
            $results['validation'] = $this->benchmarkValidation($tenant, $company->id, $ruleSets, $runs, $employees);
            $this->renderDurationsTable('Validation Job Durations (ms)', $results['validation']['durations_ms']);
            $this->renderSummary('Validation Summary', $results['validation']);
        }

        $path = ltrim((string) $this->option('output'), '/');
        $payload = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Storage::disk('local')->put($path, $payload);

        $this->info(sprintf('Baseline metrics stored at storage/app/%s', $path));

        return self::SUCCESS;
    }

    private function benchmarkImporter(Tenant $tenant, string $companyId, int $employees, int $runs): array
    {
        $durations = [];
        $dataset = $this->employeeDataset($employees);

        Tenancy::initialize($tenant);

        try {
            Employee::query()->delete();

            foreach (range(1, $runs) as $iteration) {
                $path = sprintf('baseline/%s/import-%d-%s.csv', $tenant->id, $iteration, Str::uuid());
                $this->writeCsv($path, $dataset);

                $started = microtime(true);
                ImportEmployees::dispatchSync($tenant->id, $path, $companyId);
                $durations[] = $this->durationMs($started);
            }
        } finally {
            Tenancy::end();
        }

        return $this->summaries($durations, [
            'runs' => $runs,
            'employees_per_run' => $employees,
        ]);
    }

    /**
     * @param  array<int, string>  $ruleSets
     */
    private function benchmarkValidation(Tenant $tenant, string $companyId, array $ruleSets, int $runs, int $employees): array
    {
        $durations = [];

        Tenancy::initialize($tenant);

        try {
            if (Employee::query()->count() === 0) {
                $this->seedEmployees($employees, $companyId);
            }

            foreach (range(1, $runs) as $iteration) {
                $batch = PayrollBatch::create([
                    'company_id' => $companyId,
                    'reference' => sprintf('BASELINE-%s-%d', Str::upper(Str::random(5)), $iteration),
                    'scheduled_for' => now()->addDay(),
                    'status' => 'draft',
                    'metadata' => [],
                ]);

                $started = microtime(true);
                RunBatchValidation::dispatchSync($tenant->id, $batch->id, $ruleSets);
                $durations[] = $this->durationMs($started);
            }
        } finally {
            Tenancy::end();
        }

        return $this->summaries($durations, [
            'runs' => $runs,
            'rule_sets' => $ruleSets,
        ]);
    }

    private function resolveTenant(string $identifier): ?Tenant
    {
        return Tenant::query()
            ->where('id', $identifier)
            ->orWhereHas('domains', fn ($query) => $query->where('domain', $identifier))
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function resolveRuleSets(): array
    {
        $ruleSets = array_filter((array) $this->option('rule-sets'));

        if (! empty($ruleSets)) {
            return array_values($ruleSets);
        }

        return (array) config('validation.default_sets', []);
    }

    /**
     * @return array<int, array<string, string|float>>
     */
    private function employeeDataset(int $employees): array
    {
        return collect(range(1, $employees))->map(function (int $index): array {
            return [
                'external_id' => sprintf('EMP%05d', $index),
                'first_name' => 'Employee'.$index,
                'last_name' => 'Sample'.$index,
                'email' => sprintf('employee%05d@example.test', $index),
                'phone' => sprintf('+9715%08d', $index + 100000),
                'salary' => 7500 + ($index % 10) * 125,
                'currency' => 'AED',
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, string|float>>  $dataset
     */
    private function writeCsv(string $path, array $dataset): void
    {
        $rows = [
            implode(',', ['external_id', 'first_name', 'last_name', 'email', 'phone', 'salary', 'currency']),
        ];

        foreach ($dataset as $record) {
            $rows[] = implode(',', [
                $record['external_id'],
                $record['first_name'],
                $record['last_name'],
                $record['email'],
                $record['phone'],
                $record['salary'],
                $record['currency'],
            ]);
        }

        Storage::disk('local')->put($path, implode("\n", $rows));
    }

    private function seedEmployees(int $employees, string $companyId): void
    {
        Employee::query()->delete();

        foreach ($this->employeeDataset($employees) as $record) {
            Employee::query()->create([
                'company_id' => $companyId,
                'external_id' => $record['external_id'],
                'first_name' => $record['first_name'],
                'last_name' => $record['last_name'],
                'email' => $record['email'],
                'phone' => $record['phone'],
                'salary' => $record['salary'],
                'currency' => $record['currency'],
                'metadata' => [],
            ]);
        }
    }

    /**
     * @param  array<int, float>  $durations
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function summaries(array $durations, array $context = []): array
    {
        $collection = Collection::make($durations);

        $summary = array_merge($context, [
            'durations_ms' => $durations,
            'average_ms' => $collection->avg() ? round((float) $collection->avg(), 2) : 0.0,
            'max_ms' => $collection->max() ? round((float) $collection->max(), 2) : 0.0,
            'min_ms' => $collection->min() ? round((float) $collection->min(), 2) : 0.0,
            'total_ms' => $collection->sum() ? round((float) $collection->sum(), 2) : 0.0,
        ]);

        return $summary;
    }

    private function durationMs(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }

    /**
     * @param  array<int, float>  $durations
     */
    private function renderDurationsTable(string $title, array $durations): void
    {
        $this->info($title);

        $rows = collect($durations)
            ->map(fn (float $duration, int $index) => [$index + 1, $duration])
            ->all();

        if (empty($rows)) {
            $this->warn('No runs executed.');

            return;
        }

        $this->table(['Run', 'Duration (ms)'], $rows);
    }

    private function renderSummary(string $label, array $summary): void
    {
        $this->info($label);

        $this->table([
            'Average (ms)',
            'Max (ms)',
            'Min (ms)',
            'Total (ms)',
        ], [[
            $summary['average_ms'],
            $summary['max_ms'],
            $summary['min_ms'],
            $summary['total_ms'],
        ]]);
    }
}

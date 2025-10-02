<?php

namespace App\Console\Commands;

use App\Models\PayrollBatch;
use App\Models\Tenant;
use App\Support\Audit\AuditTrailRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Stancl\Tenancy\Facades\Tenancy;

class PruneAuditTrails extends Command
{
    protected $signature = 'audit:prune
        {tenant?* : Optional tenant IDs to target}
        {--dry-run : Report pruning results without modifying data}';

    protected $description = 'Apply audit trail retention rules to all payroll batches for one or more tenants.';

    public function handle(): int
    {
        $tenantIds = collect($this->argument('tenant'))->filter()->values();
        $chunkSize = max((int) config('audit.retention.prune_chunk', 200), 50);
        $dryRun = (bool) $this->option('dry-run');

        /** @var AuditTrailRecorder $recorder */
        $recorder = App::make(AuditTrailRecorder::class);

        $tenantsQuery = Tenant::query();

        if ($tenantIds->isNotEmpty()) {
            $tenantsQuery->whereIn('id', $tenantIds->all());
        }

        $tenants = $tenantsQuery->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants matched the provided criteria.');

            return self::SUCCESS;
        }

        $tenants->each(function (Tenant $tenant) use ($recorder, $chunkSize, $dryRun): void {
            Tenancy::initialize($tenant);

            $prunedEvents = 0;
            $processedBatches = 0;

            try {
                PayrollBatch::query()
                    ->select(['id', 'metadata'])
                    ->orderBy('id')
                    ->chunkById($chunkSize, function ($batches) use ($recorder, $dryRun, &$prunedEvents, &$processedBatches): void {
                        foreach ($batches as $batch) {
                            $processedBatches++;

                            $original = $batch->metadata ?? [];
                            $retained = $recorder->enforceRetention($original);

                            $originalTrail = Arr::get($original, 'audit.trail');
                            $retainedTrail = Arr::get($retained, 'audit.trail');

                            $before = is_array($originalTrail) ? count($originalTrail) : 0;
                            $after = is_array($retainedTrail) ? count($retainedTrail) : 0;
                            $removed = max($before - $after, 0);

                            if ($removed === 0) {
                                continue;
                            }

                            $prunedEvents += $removed;

                            if (! $dryRun) {
                                $batch->forceFill(['metadata' => $retained])->saveQuietly();
                            }
                        }
                    });
            } finally {
                Tenancy::end();
            }

            $this->info(sprintf(
                'Tenant %s: processed %d batches, pruned %d audit events%s.',
                $tenant->id,
                $processedBatches,
                $prunedEvents,
                $dryRun ? ' (dry run)' : ''
            ));
        });

        return self::SUCCESS;
    }
}

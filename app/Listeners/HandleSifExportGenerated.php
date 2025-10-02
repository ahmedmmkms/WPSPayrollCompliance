<?php

namespace App\Listeners;

use App\Events\SifExportGenerated;
use App\Models\PayrollBatch;
use App\Models\Tenant;
use App\Support\Audit\AuditTrailRecorder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Tenancy;
use Throwable;

class HandleSifExportGenerated
{
    public function __construct(
        private readonly Tenancy $tenancy,
        private readonly AuditTrailRecorder $auditTrail,
    ) {}

    public function handle(SifExportGenerated $event): void
    {
        $tenant = Tenant::find($event->tenantId);

        if (! $tenant) {
            Log::warning('SIF export event tenant missing', ['tenant_id' => $event->tenantId]);

            return;
        }

        $this->tenancy->initialize($tenant);

        try {
            $batch = PayrollBatch::query()->find($event->batchId);

            if (! $batch) {
                Log::warning('SIF export event batch missing', [
                    'tenant_id' => $event->tenantId,
                    'batch_id' => $event->batchId,
                ]);

                return;
            }

            $metadata = $batch->metadata ?? [];

            $exports = collect(Arr::get($metadata, 'exports', []))
                ->map(function (array $entry) use ($event) {
                    if (($entry['queued_at'] ?? null) !== $event->queuedAt) {
                        return $entry;
                    }

                    return array_merge($entry, [
                        'status' => 'available',
                        'path' => $event->path,
                        'filename' => $event->filename,
                        'disk' => $event->disk,
                        'available_at' => now()->toISOString(),
                        'download_url' => $this->generateDownloadUrl($event),
                        'integrations' => $event->integrations,
                    ]);
                })
                ->all();

            if (empty($exports)) {
                return;
            }

            $metadata = Arr::set($metadata, 'exports', $exports);

            $matched = collect($exports)
                ->first(fn (array $entry) => ($entry['queued_at'] ?? null) === $event->queuedAt);

            if ($matched) {
                $metadata = Arr::set($metadata, 'last_export', $matched);
            }

            if ($batch->status === 'draft') {
                $batch->status = 'queued';
            }

            $metadata = $this->auditTrail->append($metadata, 'sif.generated', [
                'template' => $event->templateKey,
                'queued_at' => $event->queuedAt,
                'available_at' => Arr::get($matched, 'available_at'),
            ]);

            $batch->metadata = $metadata;
            $batch->save();
        } catch (Throwable $exception) {
            Log::error('Failed handling SIF export event', [
                'tenant_id' => $event->tenantId,
                'batch_id' => $event->batchId,
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $this->tenancy->end();
        }
    }

    private function generateDownloadUrl(SifExportGenerated $event): ?string
    {
        try {
            return Storage::disk($event->disk)->url($event->path);
        } catch (Throwable $exception) {
            Log::debug('Unable to resolve SIF download url', [
                'disk' => $event->disk,
                'path' => $event->path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

<?php

namespace App\Support\Sif;

use App\Jobs\GenerateSifExport;
use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Support\Audit\AuditTrailRecorder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Stancl\Tenancy\Tenancy as TenancyManager;

class SifExportManager
{
    public function __construct(
        private readonly SifTemplateRepository $templates,
        private readonly AuditTrailRecorder $auditTrail,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function queue(PayrollBatch $batch, ?string $templateKey = null): array
    {
        $templateKey ??= config('sif.default_template');

        $template = $this->templates->find($templateKey);

        $employees = Employee::query()
            ->where('company_id', $batch->company_id)
            ->get();

        if ($employees->isEmpty()) {
            throw new RuntimeException('Add employees to this batch before generating a SIF export.');
        }

        $tenantId = app(TenancyManager::class)->tenant?->getTenantKey();

        if ($tenantId === null) {
            throw new RuntimeException('A tenant context is required for SIF exports.');
        }

        $batchPayload = $this->buildBatchPayload($batch, $employees);
        $employeePayload = $this->buildEmployeePayload($employees);
        $queuedAt = Carbon::now()->toISOString();

        GenerateSifExport::dispatch(
            tenantId: $tenantId,
            templateKey: $templateKey,
            batch: $batchPayload,
            employees: $employeePayload,
            queuedAt: $queuedAt,
        );

        $metadataEntry = [
            'template' => $template->key,
            'template_label' => $this->resolveTemplateLabel($template),
            'queued_at' => $queuedAt,
            'disk' => config('sif.disk', 'local'),
            'status' => 'queued',
            'path' => null,
            'filename' => null,
            'download_url' => null,
            'integrations' => [],
        ];

        $metadata = $this->storeExportMetadata($batch, $metadataEntry);
        $metadata = $this->auditTrail->append($metadata, 'sif.queued', [
            'template' => $template->key,
            'queued_at' => $queuedAt,
        ]);

        $batch->update([
            'metadata' => $metadata,
        ]);

        return $metadataEntry;
    }

    private function buildBatchPayload(PayrollBatch $batch, Collection $employees): array
    {
        return [
            'id' => $batch->getKey(),
            'company_id' => $batch->company_id,
            'reference' => $batch->reference,
            'scheduled_for' => $batch->scheduled_for?->toISOString(),
            'metadata' => $batch->metadata ?? [],
            'employee_count' => $employees->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeePayload(Collection $employees): array
    {
        return $employees
            ->map(fn ($employee) => [
                'id' => $employee->getKey(),
                'company_id' => $employee->company_id,
                'external_id' => $employee->external_id,
                'salary' => (float) $employee->salary,
                'currency' => $employee->currency,
                'metadata' => $employee->metadata ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function storeExportMetadata(PayrollBatch $batch, array $entry): array
    {
        $metadata = $batch->metadata ?? [];

        $history = Arr::get($metadata, 'exports', []);
        $history[] = $entry;

        $metadata = Arr::set($metadata, 'exports', $history);
        $metadata = Arr::set($metadata, 'last_export', $entry);

        return $metadata;
    }

    private function resolveTemplateLabel(SifTemplate $template): string
    {
        $locale = app()->getLocale();

        return $template->labels[$locale] ?? $template->labels['en'] ?? $template->key;
    }
}

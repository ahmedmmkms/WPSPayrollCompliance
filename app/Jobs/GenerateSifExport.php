<?php

namespace App\Jobs;

use App\Events\SifExportGenerated;
use App\Support\Mudad\MudadAdapter;
use App\Support\Sif\SifGenerator;
use App\Support\Sif\SifTemplateRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateSifExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?string $storedPath = null;

    /**
     * @param  array<string, mixed>  $batch
     * @param  array<int, array<string, mixed>>  $employees
     */
    public function __construct(
        public string $tenantId,
        public string $templateKey,
        public array $batch,
        public array $employees,
        public string $queuedAt,
    ) {}

    public function handle(SifTemplateRepository $templates, SifGenerator $generator, MudadAdapter $mudadAdapter): void
    {
        $template = $templates->find($this->templateKey);
        $generated = $generator->generate($template, $this->batch, collect($this->employees));

        $diskName = config('sif.disk', 'local');
        $disk = Storage::disk($diskName);

        $path = 'sif/'.$this->tenantId.'/'.$generated->filename;

        try {
            $disk->put($path, $generated->contents);
        } catch (Throwable $exception) {
            Log::error('Failed to store SIF export', [
                'tenant_id' => $this->tenantId,
                'template' => $this->templateKey,
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->storedPath = $path;

        $integrations = [];

        if (($template->metadata['adapter'] ?? null) === 'mudad') {
            try {
                $result = $mudadAdapter->submit($generated, [
                    'batch' => $this->batch,
                    'employees' => $this->employees,
                    'template' => [
                        'key' => $template->key,
                        'version' => $template->version,
                    ],
                ]);

                $integrations['mudad'] = $result->toArray();
            } catch (Throwable $exception) {
                Log::error('Failed to submit Mudad payroll file', [
                    'tenant_id' => $this->tenantId,
                    'template' => $this->templateKey,
                    'message' => $exception->getMessage(),
                ]);

                $integrations['mudad'] = [
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        event(new SifExportGenerated(
            tenantId: $this->tenantId,
            templateKey: $this->templateKey,
            batchId: $this->batch['id'] ?? null,
            disk: $diskName,
            path: $path,
            filename: $generated->filename,
            queuedAt: $this->queuedAt,
            integrations: $integrations,
        ));
    }
}

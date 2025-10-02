<?php

namespace App\Support\Mudad;

use App\Support\Sif\GeneratedSif;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class MudadAdapter
{
    public function __construct(
        private readonly MudadClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function submit(GeneratedSif $sif, array $context = []): MudadSubmissionResult
    {
        $batch = $context['batch'] ?? [];
        $template = $context['template'] ?? [];
        $employees = $context['employees'] ?? [];

        $payload = [
            'reference' => $batch['reference'] ?? null,
            'template_key' => $template['key'] ?? null,
            'template_version' => $template['version'] ?? null,
            'submitted_at' => Carbon::now()->toISOString(),
            'metadata' => Arr::get($batch, 'metadata.mudad', []),
            'employees' => $employees,
            'file' => [
                'filename' => $sif->filename,
                'contents' => base64_encode($sif->contents),
            ],
        ];

        $response = $this->client->submitPayroll($payload);

        return MudadSubmissionResult::fromResponse($response);
    }

    public function status(string $submissionId): MudadSubmissionResult
    {
        $response = $this->client->queueSubmissionStatus($submissionId);

        return MudadSubmissionResult::fromResponse($response);
    }
}

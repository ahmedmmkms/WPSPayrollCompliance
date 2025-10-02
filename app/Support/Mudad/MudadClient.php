<?php

namespace App\Support\Mudad;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MudadClient
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('services.mudad', []));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function submitPayroll(array $payload): array
    {
        $response = $this->request()->post($this->resolveEndpoint('payroll/submissions'), $payload);

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function queueSubmissionStatus(string $submissionId): array
    {
        $response = $this->request()->get($this->resolveEndpoint('payroll/submissions/'.$submissionId));

        $response->throw();

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        $baseUri = $this->config['base_uri'] ?? 'https://sandbox.api.mudad.gov.sa';
        $timeout = (int) ($this->config['timeout'] ?? 15);

        $request = Http::baseUrl($baseUri)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);

        if (! empty($this->config['token'])) {
            $request = $request->withToken((string) $this->config['token']);
        }

        if (! empty($this->config['api_key'])) {
            $request = $request->withHeaders([
                'X-API-Key' => (string) $this->config['api_key'],
            ]);
        }

        if (! empty($this->config['additional_headers']) && is_array($this->config['additional_headers'])) {
            $request = $request->withHeaders($this->config['additional_headers']);
        }

        return $request;
    }

    private function resolveEndpoint(string $path): string
    {
        $prefix = $this->config['path_prefix'] ?? 'v1';
        $path = ltrim($path, '/');

        return '/'.trim($prefix.'/'.$path, '/');
    }
}

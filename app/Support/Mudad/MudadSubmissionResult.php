<?php

namespace App\Support\Mudad;

class MudadSubmissionResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly ?string $id,
        public readonly string $status,
        public readonly array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response): self
    {
        return new self(
            id: isset($response['id']) ? (string) $response['id'] : null,
            status: (string) ($response['status'] ?? 'queued'),
            payload: $response,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payload' => $this->payload,
        ];
    }
}

<?php

namespace App\Support\Audit;

use App\Models\PayrollBatch;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AuditTrailRecorder
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(PayrollBatch $batch, string $event, array $payload = []): void
    {
        $metadata = $batch->metadata ?? [];
        $metadata = $this->append($metadata, $event, $payload);
        $batch->update(['metadata' => $metadata]);
    }

    /**
     * @param  array<string, mixed>|object  $metadata
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function append(array|object $metadata, string $event, array $payload = []): array
    {
        if (! is_array($metadata)) {
            $metadata = (array) $metadata;
        }

        $entry = [
            'event' => $event,
            'occurred_at' => Carbon::now()->toISOString(),
            'payload' => $payload,
        ];

        $trail = Arr::get($metadata, 'audit.trail', []);
        if (! is_array($trail)) {
            $trail = [];
        }

        $trail[] = $entry;

        $trail = $this->trimTrail($trail);

        $metadata['audit']['trail'] = $trail;

        if (! empty($trail)) {
            $metadata['audit']['last_event'] = $trail[array_key_last($trail)];
        } else {
            Arr::forget($metadata, 'audit.last_event');
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>|object  $metadata
     * @return array<string, mixed>
     */
    public function enforceRetention(array|object $metadata): array
    {
        if (! is_array($metadata)) {
            $metadata = (array) $metadata;
        }

        $trail = Arr::get($metadata, 'audit.trail', []);

        if (! is_array($trail)) {
            $trail = [];
        }

        $trail = $this->trimTrail($trail);

        $metadata['audit']['trail'] = $trail;

        if (! empty($trail)) {
            $metadata['audit']['last_event'] = $trail[array_key_last($trail)];
        } else {
            Arr::forget($metadata, 'audit.last_event');
        }

        return $metadata;
    }

    /**
     * @param  array<int, array<string, mixed>>  $trail
     * @return array<int, array<string, mixed>>
     */
    private function trimTrail(array $trail): array
    {
        $trail = array_values(array_filter($trail, fn ($entry) => $this->withinRetentionWindow($entry)));

        $limit = max((int) config('audit.retention.max_events', 200), 0);

        if ($limit > 0 && count($trail) > $limit) {
            $trail = array_slice($trail, -1 * $limit);
        }

        return $trail;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function withinRetentionWindow(array $entry): bool
    {
        $maxAge = (int) config('audit.retention.max_age_days', 0);

        if ($maxAge <= 0) {
            return true;
        }

        $occurredAt = Arr::get($entry, 'occurred_at');

        if (! is_string($occurredAt) || $occurredAt === '') {
            return false;
        }

        try {
            $timestamp = CarbonImmutable::parse($occurredAt);
        } catch (Exception) {
            return false;
        }

        $cutoff = CarbonImmutable::now()->subDays($maxAge);

        return $timestamp->greaterThanOrEqualTo($cutoff);
    }
}

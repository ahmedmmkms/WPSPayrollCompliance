<?php

use App\Support\Audit\AuditTrailRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $now = Carbon::parse('2024-10-01 12:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow(CarbonImmutable::instance($now));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

it('prunes audit trail entries beyond the configured maximum', function (): void {
    config()->set('audit.retention.max_events', 3);
    config()->set('audit.retention.max_age_days', 0);

    $recorder = new AuditTrailRecorder;

    $metadata = [];
    $base = Carbon::parse('2024-10-01 12:00:00');

    foreach (range(1, 5) as $index) {
        $current = $base->copy()->addMinutes($index);

        Carbon::setTestNow($current);
        CarbonImmutable::setTestNow(CarbonImmutable::instance($current));

        $metadata = $recorder->append($metadata, 'event-'.$index);
    }

    $trail = data_get($metadata, 'audit.trail', []);

    expect($trail)->toHaveCount(3)
        ->and(array_column($trail, 'event'))->toEqual(['event-3', 'event-4', 'event-5']);
});

it('drops audit events older than the retention window', function (): void {
    config()->set('audit.retention.max_events', 10);
    config()->set('audit.retention.max_age_days', 30);

    $recorder = new AuditTrailRecorder;

    $base = Carbon::parse('2024-10-01 12:00:00');
    Carbon::setTestNow($base);
    CarbonImmutable::setTestNow(CarbonImmutable::instance($base));

    $metadata = [
        'audit' => [
            'trail' => [
                [
                    'event' => 'very-old',
                    'occurred_at' => $base->copy()->subDays(45)->toISOString(),
                    'payload' => [],
                ],
                [
                    'event' => 'recent',
                    'occurred_at' => $base->copy()->subDays(10)->toISOString(),
                    'payload' => [],
                ],
            ],
        ],
    ];

    $metadata = $recorder->append($metadata, 'new');

    $trail = data_get($metadata, 'audit.trail', []);
    $events = array_column($trail, 'event');

    expect($events)->toEqual(['recent', 'new']);
});

it('filters invalid timestamps from the audit trail', function (): void {
    config()->set('audit.retention.max_events', 10);
    config()->set('audit.retention.max_age_days', 30);

    $recorder = new AuditTrailRecorder;

    $metadata = [
        'audit' => [
            'trail' => [
                [
                    'event' => 'invalid-timestamp',
                    'occurred_at' => 'not-a-valid-date',
                    'payload' => [],
                ],
            ],
        ],
    ];

    $metadata = $recorder->append($metadata, 'valid-event');

    $trail = data_get($metadata, 'audit.trail', []);
    $events = array_column($trail, 'event');

    expect($events)->toEqual(['valid-event']);
});

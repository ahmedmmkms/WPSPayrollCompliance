<?php

return [
    'retention' => [
        'max_events' => (int) env('AUDIT_TRAIL_MAX_EVENTS', 200),
        'max_age_days' => (int) env('AUDIT_TRAIL_MAX_AGE_DAYS', 180),
        'policy_version' => env('AUDIT_TRAIL_POLICY_VERSION', '2024-10-01'),
        'prune_chunk' => (int) env('AUDIT_TRAIL_PRUNE_CHUNK', 200),
    ],
];

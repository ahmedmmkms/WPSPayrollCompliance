<?php

return [
    'use' => env('HORIZON_QUEUE_CONNECTION', 'redis'),

    'prefix' => env('HORIZON_PREFIX', 'horizon'),

    'domain' => env('HORIZON_DOMAIN'),

    'middleware' => ['web'],

    'waits' => [
        'redis:imports' => 90,
        'redis:validation' => 150,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 1440,
        'failed' => 10080,
        'monitored' => 1440,
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'environments' => [
        'production' => [
            'imports-supervisor' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', 'redis'),
                'queue' => ['imports'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => (int) env('HORIZON_IMPORTS_MAX_PROCESSES', 6),
                'balanceCooldown' => 5,
                'balanceMaxShift' => 1,
                'tries' => 3,
                'timeout' => 90,
            ],
            'validation-supervisor' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', 'redis'),
                'queue' => ['validation'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => (int) env('HORIZON_VALIDATION_MAX_PROCESSES', 8),
                'balanceCooldown' => 5,
                'balanceMaxShift' => 1,
                'tries' => 3,
                'timeout' => 150,
            ],
        ],

        'staging' => [
            'imports-supervisor' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', 'redis'),
                'queue' => ['imports'],
                'balance' => 'simple',
                'maxProcesses' => 3,
                'tries' => 1,
                'timeout' => 90,
            ],
            'validation-supervisor' => [
                'connection' => env('HORIZON_QUEUE_CONNECTION', 'redis'),
                'queue' => ['validation'],
                'balance' => 'simple',
                'maxProcesses' => 4,
                'tries' => 1,
                'timeout' => 150,
            ],
        ],

        'local' => [
            'imports-supervisor' => [
                'connection' => 'redis',
                'queue' => ['imports'],
                'balance' => 'simple',
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 90,
            ],
            'validation-supervisor' => [
                'connection' => 'redis',
                'queue' => ['validation'],
                'balance' => 'simple',
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 150,
            ],
        ],
    ],
];

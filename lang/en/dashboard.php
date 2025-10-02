<?php

return [
    'kpi' => [
        'title' => 'KPI dashboard',
        'description' => 'Monitor payroll throughput and exception trends for your tenant.',
        'cards' => [
            'throughput' => [
                'title' => 'Payroll throughput',
                'subtitle' => 'Status changes across the last 14 days',
            ],
            'exceptions' => [
                'title' => 'Exception flow',
                'subtitle' => 'Opened vs resolved exceptions across the last 14 days',
            ],
        ],
        'status_heading' => 'Current exception load',
        'sla_heading' => 'SLA breaches',
        'updated_at_label' => 'Updated {time}',
        'empty_state' => 'No KPI activity captured yet. Once batches and exceptions are recorded, the dashboard will populate automatically.',
    ],
];

<?php

return [
    'sla' => [
        'label' => 'SLA timer',
        'due_in' => 'Due in :time',
        'due_now' => 'Due now',
        'just_overdue' => 'Just overdue',
        'overdue' => 'Overdue by :time',
        'no_due' => 'No due date',
        'resolved' => 'Resolved',
    ],
    'statuses' => [
        'open' => 'Open',
        'in_review' => 'In review',
        'resolved' => 'Resolved',
    ],
    'activity' => [
        'heading' => 'Activity',
        'events' => 'Events',
        'empty' => 'No activity recorded yet.',
        'event' => 'Event',
        'occurred_at' => 'Occurred at',
        'failures' => 'Failures',
        'rule_sets' => 'Rule sets',
        'template' => 'Template',
        'available_at' => 'Available at',
        'unknown' => 'Unknown event',
    ],
    'notifications' => [
        'common' => [
            'unknown_employee' => 'Unknown employee',
            'unassigned' => 'Unassigned',
        ],
        'status_changed' => [
            'title' => 'Exception status updated',
            'body' => 'Status changed from :previous_status to :current_status for :employee in batch :reference.',
            'none' => 'Not set',
        ],
        'assignment_changed' => [
            'title' => 'Exception assignment updated',
            'body_assigned' => ':assignee is now assigned to :employee in batch :reference.',
            'body_unassigned' => 'Assignment cleared for :employee in batch :reference.',
        ],
    ],
];

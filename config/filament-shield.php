<?php

return [
    'guard' => env('FILAMENT_SHIELD_GUARD', 'web'),
    'super_admin' => 'Super Admin',
    'default_roles' => [
        'admin',
    ],
    'default_permissions' => [
        'employees.view',
        'payroll.view',
    ],
    'generator' => [
        'option' => 'resource_policy',
        'policy' => [
            'generate_for_resources' => false,
        ],
    ],
];

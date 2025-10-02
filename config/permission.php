<?php

return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'model_morph_key' => 'model_id',
    ],

    'teams' => false,

    'register_permission_check_method' => true,

    'display_permission_in_exception' => false,

    'enable_wildcard_permission' => true,

    'cache' => [
        'expiration_time' => now()->addDay(),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],
];

<?php

use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager;
use Stancl\Tenancy\UUIDGenerator;

$defaultTenantDomain = env('DEFAULT_TENANT_DOMAIN');

if (! $defaultTenantDomain) {
    $appUrl = env('APP_URL');

    if ($appUrl) {
        $defaultTenantDomain = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
    }
}

return [
    'tenant_model' => App\Models\Tenant::class,
    'id_generator' => UUIDGenerator::class,
    'domain_model' => Domain::class,
    'default_tenant' => [
        'id' => env('DEFAULT_TENANT_ID'),
        'name' => env('DEFAULT_TENANT_NAME', 'Default Tenant'),
        'domain' => $defaultTenantDomain,
        'email' => env('DEFAULT_TENANT_EMAIL'),
        'seed' => env('DEFAULT_TENANT_SEED', true),
    ],
    'central_domains' => [
        env('CENTRAL_DOMAIN', 'localhost'),
    ],
    'middleware' => [
        'initialize_tenancy_by_domain' => InitializeTenancyByDomain::class,
        'prevent_access_from_central_domains' => PreventAccessFromCentralDomains::class,
    ],
    'bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
        CacheTenancyBootstrapper::class,
        QueueTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
    ],
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),
        'template_tenant_connection' => env('TENANT_DB_TEMPLATE_CONNECTION'),
        'prefix' => env('TENANT_DB_PREFIX', 'tenant'),
        'suffix' => env('TENANT_DB_SUFFIX', ''),
        'managers' => [
            'sqlite' => SQLiteDatabaseManager::class,
            'mysql' => MySQLDatabaseManager::class,
            'pgsql' => env('TENANT_DB_SCHEMA_MODE') === 'schema'
                ? PostgreSQLSchemaManager::class
                : PostgreSQLDatabaseManager::class,
        ],
    ],
    'cache' => [
        'tag_base' => env('TENANCY_CACHE_TAG_BASE', 'tenant'),
    ],
    'filesystem' => [
        'suffix_base' => env('TENANCY_FILESYSTEM_SUFFIX_BASE', 'tenant'),
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],
    'redis' => [
        'prefix_base' => env('TENANCY_REDIS_PREFIX_BASE', 'tenant'),
        'prefixed_connections' => [],
    ],
    'features' => [],
    'routes' => true,
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];

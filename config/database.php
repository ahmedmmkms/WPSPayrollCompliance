<?php

use Illuminate\Support\Str;

$defaultConnection = env('DB_CONNECTION', 'pgsql');
$tenantDriver = env('TENANT_DB_CONNECTION', $defaultConnection);
$tenantUsesSqlite = $tenantDriver === 'sqlite';
$tenantDefaultPort = $tenantDriver === 'pgsql' ? '5432' : '3306';

return [

    'default' => $defaultConnection,

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL'))),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'tenant' => [
            'driver' => $tenantDriver,
            'url' => env('TENANT_DB_URL', env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL')))),
            'host' => $tenantUsesSqlite ? null : env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => $tenantUsesSqlite ? null : env('TENANT_DB_PORT', env('DB_PORT', $tenantDefaultPort)),
            'database' => env('TENANT_DB_DATABASE', $tenantUsesSqlite
                ? database_path('tenant.sqlite')
                : env('DB_DATABASE')
            ),
            'username' => $tenantUsesSqlite ? null : env('TENANT_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => $tenantUsesSqlite ? null : env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => $tenantUsesSqlite ? null : env('TENANT_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => $tenantUsesSqlite ? null : env('TENANT_DB_CHARSET', $tenantDriver === 'pgsql'
                ? env('DB_CHARSET', 'utf8')
                : env('DB_CHARSET', 'utf8mb4')
            ),
            'collation' => $tenantUsesSqlite ? null : ($tenantDriver === 'pgsql'
                ? null
                : env('TENANT_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci'))
            ),
            'prefix' => '',
            'prefix_indexes' => true,
            'foreign_key_constraints' => $tenantUsesSqlite ? env('DB_FOREIGN_KEYS', true) : null,
            'strict' => $tenantUsesSqlite ? null : true,
            'engine' => null,
            'sslmode' => $tenantUsesSqlite ? null : env('TENANT_DB_SSLMODE', env('DB_SSLMODE', 'prefer')),
            'options' => match ($tenantDriver) {
                'mysql', 'mariadb' => extension_loaded('pdo_mysql') ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
                default => [],
            },
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL'))),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL'))),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL'))),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL', env('DATABASE_URL', env('NEON_DATABASE_URL'))),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];

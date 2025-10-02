<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Bootstrappers\CacheBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueBootstrapper;
use Stancl\Tenancy\Bootstrappers\StorageBootstrapper;
use Stancl\Tenancy\Tenancy;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Tenancy::macro('bootstrappers', function () {
            return [
                CacheBootstrapper::class,
                QueueBootstrapper::class,
                StorageBootstrapper::class,
            ];
        });
    }

    public function boot(): void
    {
        // TODO: Map tenant routes when tenancy routing is finalized.
    }
}

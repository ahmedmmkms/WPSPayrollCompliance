<?php

namespace App\Providers;

use App\Events\SifExportGenerated;
use App\Listeners\HandleSifExportGenerated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SifExportGenerated::class => [
            HandleSifExportGenerated::class,
        ],
    ];
}

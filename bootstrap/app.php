<?php

use App\Console\Commands\CreateTenant;
use App\Console\Commands\DeleteTenant;
use App\Console\Commands\PruneAuditTrails;
use App\Console\Commands\QueueBaseline;
use App\Console\Commands\SeedTenant;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(__DIR__.'/../routes/tenant.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->appendToGroup('web', InitializeTenancyByDomain::class);
        $middleware->appendToGroup('web', PreventAccessFromCentralDomains::class);

        $middleware->priority([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);
    })
    ->withCommands([
        CreateTenant::class,
        DeleteTenant::class,
        QueueBaseline::class,
        PruneAuditTrails::class,
        SeedTenant::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('audit:prune')->dailyAt('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

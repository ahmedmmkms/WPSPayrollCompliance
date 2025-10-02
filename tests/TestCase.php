<?php

namespace Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        config()->set('tenancy.central_domains', ['localhost', '127.0.0.1']);

        $databasePath = config('database.connections.sqlite.database');

        if ($databasePath && $databasePath !== ':memory:' && ! file_exists($databasePath)) {
            $directory = dirname($databasePath);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            touch($databasePath);
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        app()->resolving(Schedule::class, function (Schedule $schedule): void {
            $alreadyRegistered = collect($schedule->events())
                ->contains(fn ($event) => str_contains($event->command ?? '', 'horizon:snapshot'));

            if (! $alreadyRegistered) {
                $schedule->command('horizon:snapshot')->everyFiveMinutes();
            }
        });
    }
}

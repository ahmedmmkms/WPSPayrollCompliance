<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

it('schedules horizon snapshot every five minutes', function (): void {
    config([
        'cache.default' => 'array',
    ]);

    app()->forgetInstance(Schedule::class);

    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)->toContain('horizon:snapshot')
        ->and($output)->toContain('*/5 * * * *');
});

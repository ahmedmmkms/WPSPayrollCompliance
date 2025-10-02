<?php

use App\Http\Controllers\Kpi\ExceptionMetricsController;
use App\Http\Controllers\Kpi\ThroughputMetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->group(function () {
        Route::view('/dashboard', 'landing')->name('dashboard');

        Route::prefix('admin/kpi')
            ->name('tenant.kpi.')
            ->group(function () {
                Route::get('throughput', ThroughputMetricsController::class)->name('throughput');
                Route::get('exceptions', ExceptionMetricsController::class)->name('exceptions');
            });
    });

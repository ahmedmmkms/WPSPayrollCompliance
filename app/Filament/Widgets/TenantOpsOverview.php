<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TenantOpsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $employeeCount = Employee::query()->count();
        $openExceptions = PayrollException::query()->whereIn('status', ['open', 'in_review'])->count();
        $queuedBatches = PayrollBatch::query()->whereIn('status', ['queued', 'processing'])->count();

        return [
            Stat::make('Employees', Number::format($employeeCount))
                ->description('Active records across companies')
                ->icon('heroicon-o-users'),
            Stat::make('Open Exceptions', Number::format($openExceptions))
                ->description($openExceptions === 0 ? 'All clear' : 'Requires follow-up')
                ->color($openExceptions === 0 ? 'success' : 'danger')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Queued Batches', Number::format($queuedBatches))
                ->description('Awaiting export or approval')
                ->icon('heroicon-o-queue-list'),
        ];
    }
}

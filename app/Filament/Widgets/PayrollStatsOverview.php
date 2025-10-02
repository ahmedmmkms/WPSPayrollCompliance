<?php

namespace App\Filament\Widgets;

use App\Models\PayrollBatch;
use App\Models\PayrollException;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PayrollStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $tenantId = tenant()->id;

        // Get batch stats
        $totalBatches = PayrollBatch::count();
        $completedBatches = PayrollBatch::where('status', 'approved')->count();
        $failedBatches = PayrollBatch::where('status', 'rejected')->count();

        // Get exception stats
        $openExceptions = PayrollException::whereIn('status', ['open', 'in_review'])->count();
        $resolvedExceptions = PayrollException::where('status', 'resolved')->count();

        return [
            Stat::make('Total Batches', number_format($totalBatches))
                ->description('All payroll batches processed')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Completed', number_format($completedBatches))
                ->description('Successfully processed batches')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Open Exceptions', number_format($openExceptions))
                ->description('Unresolved issues requiring attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];
    }
}
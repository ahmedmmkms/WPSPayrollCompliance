<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttentionListWidget;
use App\Filament\Widgets\PayrollBatchesTrendChart;
use App\Filament\Widgets\PayrollStatsOverview;
use App\Filament\Widgets\TenantOpsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Tenant Overview';

    public function getWidgets(): array
    {
        return [
            PayrollStatsOverview::class,
            PayrollBatchesTrendChart::class,
            TenantOpsOverview::class,
            AttentionListWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('import_employees')
                ->label('Import Employees')
                ->url('/admin/employees/import')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary'),
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }
}

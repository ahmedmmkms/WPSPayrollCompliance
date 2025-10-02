<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KpiDashboard extends Page
{
    protected static string $view = 'filament.pages.kpi-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 2;

    public function getTitle(): string
    {
        return __('dashboard.kpi.title');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.kpi.description');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.kpi.title');
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\PayrollBatch;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PayrollBatchesTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Payroll Batches (Last 30 Days)';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $trend = Trend::model(PayrollBatch::class)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Payroll Batches',
                    'data' => $trend->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => '#dbeafe',
                ],
            ],
            'labels' => $trend->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
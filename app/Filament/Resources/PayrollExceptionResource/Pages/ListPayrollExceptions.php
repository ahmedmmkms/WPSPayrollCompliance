<?php

namespace App\Filament\Resources\PayrollExceptionResource\Pages;

use App\Filament\Resources\PayrollExceptionResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class ListPayrollExceptions extends ListRecords
{
    protected static string $resource = PayrollExceptionResource::class;

    protected ?array $statusMetricsCache = null;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $metrics = $this->getStatusMetrics();
        $now = now();

        return [
            'all' => Tab::make(__('filament.resources.payroll_exception.tabs.all'))
                ->icon('heroicon-m-queue-list')
                ->badge(Number::format($metrics['all']))
                ->badgeColor($metrics['all'] > 0 ? 'primary' : 'gray'),
            'open' => Tab::make(__('filament.resources.payroll_exception.tabs.open'))
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(Number::format($metrics['open']))
                ->badgeColor($metrics['open'] > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open')),
            'in_review' => Tab::make(__('filament.resources.payroll_exception.tabs.in_review'))
                ->icon('heroicon-m-adjustments-horizontal')
                ->badge(Number::format($metrics['in_review']))
                ->badgeColor($metrics['in_review'] > 0 ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_review')),
            'resolved' => Tab::make(__('filament.resources.payroll_exception.tabs.resolved'))
                ->icon('heroicon-m-check-circle')
                ->badge(Number::format($metrics['resolved']))
                ->badgeColor($metrics['resolved'] > 0 ? 'success' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'resolved')),
            'overdue' => Tab::make(__('filament.resources.payroll_exception.tabs.overdue'))
                ->icon('heroicon-m-clock')
                ->badge(Number::format($metrics['overdue']))
                ->badgeColor($metrics['overdue'] > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereIn('status', ['open', 'in_review'])
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', $now)),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }

    protected function getStatusMetrics(): array
    {
        if ($this->statusMetricsCache !== null) {
            return $this->statusMetricsCache;
        }

        $query = $this->newBaseMetricsQuery();

        $statusCounts = (clone $query)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $overdueCount = (clone $query)
            ->whereIn('status', ['open', 'in_review'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        return $this->statusMetricsCache = [
            'all' => (int) $statusCounts->sum(),
            'open' => (int) ($statusCounts['open'] ?? 0),
            'in_review' => (int) ($statusCounts['in_review'] ?? 0),
            'resolved' => (int) ($statusCounts['resolved'] ?? 0),
            'overdue' => $overdueCount,
        ];
    }

    protected function newBaseMetricsQuery(): Builder
    {
        /** @var class-string<PayrollExceptionResource> $resource */
        $resource = static::$resource;

        return $resource::getEloquentQuery();
    }
}

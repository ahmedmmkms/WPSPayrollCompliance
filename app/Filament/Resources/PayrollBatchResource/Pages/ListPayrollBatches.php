<?php

namespace App\Filament\Resources\PayrollBatchResource\Pages;

use App\Filament\Resources\PayrollBatchResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListPayrollBatches extends ListRecords
{
    protected static string $resource = PayrollBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label(__('filament.resources.payroll_batch.table.empty_state.action')),
        ];
    }
}

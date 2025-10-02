<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label(__('filament.resources.company.table.empty_state.action')),
        ];
    }
}

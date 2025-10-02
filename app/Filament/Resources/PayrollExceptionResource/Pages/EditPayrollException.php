<?php

namespace App\Filament\Resources\PayrollExceptionResource\Pages;

use App\Filament\Resources\PayrollExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollException extends EditRecord
{
    protected static string $resource = PayrollExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}

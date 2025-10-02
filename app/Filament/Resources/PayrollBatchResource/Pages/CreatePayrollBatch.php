<?php

namespace App\Filament\Resources\PayrollBatchResource\Pages;

use App\Filament\Resources\PayrollBatchResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePayrollBatch extends CreateRecord
{
    protected static string $resource = PayrollBatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference'] = $data['reference'] ?: strtoupper(Str::random(12));

        return $data;
    }
}

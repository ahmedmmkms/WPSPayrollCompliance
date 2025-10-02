<?php

namespace App\Filament\Widgets;

use App\Models\PayrollException;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AttentionListWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Unresolved Exceptions Requiring Attention';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PayrollException::query()
                    ->whereIn('status', ['open', 'in_review'])
                    ->with(['employee', 'batch'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->employee?->first_name . ' ' . $record->employee?->last_name),
                Tables\Columns\TextColumn::make('batch.reference')
                    ->label('Batch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rule_id')
                    ->label('Rule')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->colors([
                        'warning' => 'warning',
                        'danger' => 'error',
                        'info' => 'info',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.payroll-exceptions.edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->striped();
    }
}
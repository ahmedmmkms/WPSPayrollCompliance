<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('filament.resources.employee.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.employee.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.employee.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('filament.resources.employee.form.sections.details'))
                    ->schema([
                        Select::make('company_id')
                            ->label(__('filament.resources.employee.form.fields.company'))
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('external_id')
                            ->label(__('filament.resources.employee.form.fields.external_id'))
                            ->maxLength(64)
                            ->disabledOn('edit'),
                        TextInput::make('first_name')
                            ->label(__('filament.resources.employee.form.fields.first_name'))
                            ->required()
                            ->maxLength(120),
                        TextInput::make('last_name')
                            ->label(__('filament.resources.employee.form.fields.last_name'))
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->label(__('filament.resources.employee.form.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label(__('filament.resources.employee.form.fields.phone'))
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('salary')
                            ->label(__('filament.resources.employee.form.fields.salary'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('AED')
                            ->required(),
                        TextInput::make('currency')
                            ->label(__('filament.resources.employee.form.fields.currency'))
                            ->maxLength(3)
                            ->default('AED')
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('filament.resources.employee.table.columns.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('external_id')
                    ->label(__('filament.resources.employee.table.columns.external_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('first_name')
                    ->label(__('filament.resources.employee.table.columns.first_name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_name')
                    ->label(__('filament.resources.employee.table.columns.last_name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('filament.resources.employee.table.columns.email'))
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('salary')
                    ->label(__('filament.resources.employee.table.columns.salary'))
                    ->money(fn (Employee $record) => $record->currency ?? 'AED')
                    ->sortable()
                    ->toggleable(),
                BadgeColumn::make('currency')
                    ->colors([
                        'primary',
                    ])
                    ->label(__('filament.resources.employee.table.columns.currency'))
                    ->toggleable(),
                IconColumn::make('exceptions_count')
                    ->label(__('filament.resources.employee.table.columns.exceptions'))
                    ->icon(fn (Employee $record) => $record->exceptions()->count() > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                    ->color(fn (Employee $record) => $record->exceptions()->count() > 0 ? 'warning' : 'success')
                    ->tooltip(fn (Employee $record) => $record->exceptions()->count() > 0 ? 'Has ' . $record->exceptions()->count() . ' exceptions' : 'No exceptions')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('filament.resources.employee.table.columns.updated_at'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('last_name')
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->label(__('filament.resources.employee.table.filters.company')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('filament.resources.employee.table.empty_state.heading'))
            ->emptyStateDescription(__('filament.resources.employee.table.empty_state.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('filament.resources.employee.table.empty_state.action')),
            ])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'import' => Pages\ImportEmployeesWizard::route('/import'),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make(),
            Tables\Actions\Action::make('import')
                ->label('Import Employees')
                ->url(static::getUrl('import'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('company', 'exceptions');
    }
}

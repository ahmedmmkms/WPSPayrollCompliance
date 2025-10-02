<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('filament.resources.company.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.company.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.company.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('filament.resources.company.form.sections.details'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('filament.resources.company.form.fields.name'))
                        ->required()
                        ->maxLength(150),
                    TextInput::make('trade_license')
                        ->label(__('filament.resources.company.form.fields.trade_license'))
                        ->maxLength(100),
                    TextInput::make('contact_email')
                        ->label(__('filament.resources.company.form.fields.contact_email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('metadata.contact_phone')
                        ->label(__('filament.resources.company.form.fields.contact_phone'))
                        ->tel()
                        ->maxLength(40),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament.resources.company.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trade_license')
                    ->label(__('filament.resources.company.table.columns.trade_license'))
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('contact_email')
                    ->label(__('filament.resources.company.table.columns.contact_email'))
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage(__('filament.resources.company.table.columns.contact_email_copy'))
                    ->searchable(),
                TextColumn::make('metadata.contact_phone')
                    ->label(__('filament.resources.company.table.columns.contact_phone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label(__('filament.resources.company.table.columns.updated_at')),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
            ])
            ->emptyStateHeading(__('filament.resources.company.table.empty_state.heading'))
            ->emptyStateDescription(__('filament.resources.company.table.empty_state.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('filament.resources.company.table.empty_state.action')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('name');
    }
}

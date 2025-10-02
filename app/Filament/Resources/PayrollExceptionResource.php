<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollExceptionResource\Pages;
use App\Models\PayrollException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PayrollExceptionResource extends Resource
{
    protected static ?string $model = PayrollException::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('filament.resources.payroll_exception.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.payroll_exception.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.groups.compliance');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.payroll_exception.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('filament.resources.payroll_exception.form.sections.status'))
                ->schema([
                    Select::make('status')
                        ->label(__('filament.resources.payroll_exception.form.fields.status'))
                        ->options([
                            'open' => __('exceptions.statuses.open'),
                            'in_review' => __('exceptions.statuses.in_review'),
                            'resolved' => __('exceptions.statuses.resolved'),
                        ])
                        ->required(),
                    TextInput::make('assigned_to')
                        ->label(__('filament.resources.payroll_exception.form.fields.assignee'))
                        ->maxLength(120),
                    DateTimePicker::make('due_at')
                        ->label(__('filament.resources.payroll_exception.form.fields.due_at'))
                        ->seconds(false),
                    DateTimePicker::make('resolved_at')
                        ->label(__('filament.resources.payroll_exception.form.fields.resolved_at'))
                        ->seconds(false),
                    Textarea::make('metadata.notes')
                        ->label(__('filament.resources.payroll_exception.form.fields.notes'))
                        ->columnSpanFull()
                        ->rows(3),
                ])->columns(2),
            Section::make(__('filament.resources.payroll_exception.form.sections.context'))
                ->schema([
                    Placeholder::make('rule_id')
                        ->label(__('filament.resources.payroll_exception.form.fields.rule_id'))
                        ->content(fn (?PayrollException $record) => $record?->rule_id ?? '—'),
                    Placeholder::make('rule_set_id')
                        ->label(__('filament.resources.payroll_exception.form.fields.rule_set'))
                        ->content(fn (?PayrollException $record) => $record?->rule_set_id ?? '—'),
                    Placeholder::make('severity')
                        ->label(__('filament.resources.payroll_exception.form.fields.severity'))
                        ->content(fn (?PayrollException $record) => Str::title($record?->severity ?? 'error')),
                    Placeholder::make('scope')
                        ->label(__('filament.resources.payroll_exception.form.fields.scope'))
                        ->content(fn (?PayrollException $record) => Str::title(data_get($record?->context, 'scope', 'batch'))),
                    Placeholder::make('external_id')
                        ->label(__('filament.resources.payroll_exception.form.fields.external_id'))
                        ->content(fn (?PayrollException $record) => data_get($record?->context, 'external_id', '—')),
                    Placeholder::make('value')
                        ->label(__('filament.resources.payroll_exception.form.fields.value'))
                        ->content(fn (?PayrollException $record) => (string) (data_get($record?->metadata, 'value') ?? data_get($record?->context, 'value', '—'))),
                    Textarea::make('message.en')
                        ->label(__('filament.resources.payroll_exception.form.fields.message_en'))
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    /**
     * @return array{color: string, text: string}
     */
    protected static function getSlaMeta(PayrollException $record): array
    {
        $dueAt = $record->due_at;

        if ($record->status === 'resolved') {
            return [
                'text' => __('exceptions.sla.resolved'),
                'color' => 'gray',
            ];
        }

        if (! $dueAt instanceof Carbon) {
            return [
                'text' => __('exceptions.sla.no_due'),
                'color' => 'gray',
            ];
        }

        $now = now();
        $secondsDiff = $now->diffInSeconds($dueAt, false);

        if ($secondsDiff === 0) {
            return [
                'text' => __('exceptions.sla.due_now'),
                'color' => 'danger',
            ];
        }

        $locale = app()->getLocale();
        $intervalText = CarbonInterval::seconds(abs($secondsDiff))
            ->cascade()
            ->locale($locale)
            ->forHumans([
                'short' => true,
                'parts' => 2,
                'join' => true,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]);

        if ($secondsDiff > 0) {
            if ($secondsDiff <= 60) {
                return [
                    'text' => __('exceptions.sla.due_now'),
                    'color' => 'warning',
                ];
            }

            $color = $secondsDiff <= 3600 ? 'warning' : 'success';

            return [
                'text' => __('exceptions.sla.due_in', ['time' => $intervalText]),
                'color' => $color,
            ];
        }

        if ($secondsDiff >= -60) {
            return [
                'text' => __('exceptions.sla.just_overdue'),
                'color' => 'danger',
            ];
        }

        return [
            'text' => __('exceptions.sla.overdue', ['time' => $intervalText]),
            'color' => 'danger',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function getActivityFeed(PayrollException $record): array
    {
        $batch = $record->payrollBatch;

        if ($batch === null) {
            return [];
        }

        $trail = data_get($batch->metadata, 'audit.trail');

        if (! is_array($trail)) {
            return [];
        }

        return collect($trail)
            ->filter(fn ($entry) => is_array($entry))
            ->map(function (array $entry): array {
                $payload = $entry['payload'] ?? [];

                if (! is_array($payload)) {
                    $payload = (array) $payload;
                }

                return [
                    ...$entry,
                    'event' => (string) ($entry['event'] ?? ''),
                    'occurred_at' => $entry['occurred_at'] ?? null,
                    'payload' => $payload,
                ];
            })
            ->sortByDesc(fn (array $entry) => $entry['occurred_at'] ?? '')
            ->take(20)
            ->values()
            ->all();
    }

    protected static function getActivityLabel(string $event): string
    {
        $event = trim($event);

        if ($event === '') {
            return __('exceptions.activity.unknown');
        }

        return Str::headline(str_replace('.', ' ', $event));
    }

    protected static function getActivityColor(string $event): string
    {
        return match ($event) {
            'validation.run' => 'info',
            'sif.queued' => 'warning',
            'sif.generated' => 'success',
            default => 'gray',
        };
    }

    /**
     * @return array<int, TextEntry>
     */
    protected static function getActivityEntrySchema(): array
    {
        return [
            TextEntry::make('event')
                ->label(__('exceptions.activity.event'))
                ->badge()
                ->formatStateUsing(fn (?string $state): string => static::getActivityLabel($state ?? ''))
                ->color(fn (?string $state): string => static::getActivityColor($state ?? '')),
            TextEntry::make('occurred_at')
                ->label(__('exceptions.activity.occurred_at'))
                ->dateTime(),
            TextEntry::make('payload.summary.failures')
                ->label(__('exceptions.activity.failures'))
                ->hidden(fn (array $state): bool => ! isset($state['payload']['summary']['failures'])),
            TextEntry::make('payload.rule_sets')
                ->label(__('exceptions.activity.rule_sets'))
                ->formatStateUsing(function ($state): string {
                    if (is_array($state)) {
                        return collect($state)
                            ->filter(fn ($value) => filled($value))
                            ->map(fn ($value) => (string) $value)
                            ->implode(', ');
                    }

                    return (string) ($state ?? '');
                })
                ->hidden(fn (array $state): bool => empty($state['payload']['rule_sets'] ?? [])),
            TextEntry::make('payload.template')
                ->label(__('exceptions.activity.template'))
                ->hidden(fn (array $state): bool => ! isset($state['payload']['template'])),
            TextEntry::make('payload.available_at')
                ->label(__('exceptions.activity.available_at'))
                ->dateTime()
                ->hidden(fn (array $state): bool => ! isset($state['payload']['available_at'])),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->heading(__('filament.resources.payroll_exception.table.heading'))
            ->description(__('filament.resources.payroll_exception.table.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('payrollBatch.reference')
                            ->label(__('filament.resources.payroll_exception.table.columns.batch'))
                            ->icon('heroicon-m-clipboard-document-list')
                            ->iconPosition(IconPosition::Before)
                            ->weight(FontWeight::Medium)
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('employee.external_id')
                            ->label(__('filament.resources.payroll_exception.table.columns.employee'))
                            ->icon('heroicon-m-identification')
                            ->iconPosition(IconPosition::Before)
                            ->sortable()
                            ->searchable()
                            ->formatStateUsing(fn (?string $state): string => $state ?? __('filament.resources.payroll_exception.table.columns.unassigned'))
                            ->wrap(),
                        TextColumn::make('rule_id')
                            ->label(__('filament.resources.payroll_exception.table.columns.rule'))
                            ->badge()
                            ->color('gray')
                            ->wrap()
                            ->searchable(),
                    ])->grow(),
                    Stack::make([
                        TextColumn::make('status')
                            ->label(__('filament.resources.payroll_exception.table.columns.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'resolved' => 'success',
                                'in_review' => 'warning',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => __('exceptions.statuses.' . $state)),
                        TextColumn::make('severity')
                            ->label(__('filament.resources.payroll_exception.table.columns.severity'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'warning' => 'warning',
                                'info' => 'info',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => Str::title($state)),
                        TextColumn::make('due_at')
                            ->label(__('exceptions.sla.label'))
                            ->badge()
                            ->icon('heroicon-m-clock')
                            ->iconPosition(IconPosition::Before)
                            ->sortable()
                            ->formatStateUsing(fn (?Carbon $state, PayrollException $record): string => static::getSlaMeta($record)['text'])
                            ->color(fn (PayrollException $record): string => static::getSlaMeta($record)['color'])
                            ->tooltip(fn (?Carbon $state): ?string => $state?->locale(app()->getLocale())->isoFormat('LLL'))
                            ->alignEnd(),
                        TextColumn::make('assigned_to')
                            ->label(__('filament.resources.payroll_exception.table.columns.assignee'))
                            ->icon('heroicon-m-user-circle')
                            ->iconPosition(IconPosition::Before)
                            ->formatStateUsing(fn (?string $state): string => $state ?? __('filament.resources.payroll_exception.table.columns.unassigned'))
                            ->searchable(),
                        TextColumn::make('updated_at')
                            ->label(__('filament.resources.payroll_exception.table.columns.updated_at'))
                            ->since()
                            ->dateTimeTooltip()
                            ->alignEnd(),
                    ])
                        ->alignment(Alignment::End)
                        ->space(1)
                        ->grow(false),
                ])->from('md'),
            ])
            ->recordClasses(fn (PayrollException $record): string => match ($record->status) {
                'resolved' => 'border-s-2 border-success-500/70 dark:border-success-400/40',
                'in_review' => 'border-s-2 border-warning-400/80 dark:border-yellow-400/50',
                default => 'border-s-2 border-rose-500/70 dark:border-rose-400/60',
            })
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament.resources.payroll_exception.table.filters.status'))
                    ->multiple()
                    ->preload()
                    ->indicator(__('filament.resources.payroll_exception.table.filters.status'))
                    ->options([
                        'open' => __('exceptions.statuses.open'),
                        'in_review' => __('exceptions.statuses.in_review'),
                        'resolved' => __('exceptions.statuses.resolved'),
                    ]),
                SelectFilter::make('severity')
                    ->label(__('filament.resources.payroll_exception.table.filters.severity'))
                    ->multiple()
                    ->preload()
                    ->indicator(__('filament.resources.payroll_exception.table.filters.severity'))
                    ->options([
                        'error' => __('filament.resources.payroll_exception.table.severity.error'),
                        'warning' => __('filament.resources.payroll_exception.table.severity.warning'),
                        'info' => __('filament.resources.payroll_exception.table.severity.info'),
                    ]),
                Filter::make('due_between')
                    ->label(__('filament.resources.payroll_exception.table.filters.due_between'))
                    ->form([
                        DatePicker::make('from')
                            ->label(__('filament.resources.payroll_exception.table.filters.from')),
                        DatePicker::make('until')
                            ->label(__('filament.resources.payroll_exception.table.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $innerQuery, string $date) => $innerQuery->whereDate('due_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $innerQuery, string $date) => $innerQuery->whereDate('due_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = __('filament.resources.payroll_exception.table.indicators.from', ['date' => Carbon::parse($data['from'])->isoFormat('ll')]);
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = __('filament.resources.payroll_exception.table.indicators.until', ['date' => Carbon::parse($data['until'])->isoFormat('ll')]);
                        }

                        return $indicators;
                    }),
                TernaryFilter::make('assigned_to')
                    ->label(__('filament.resources.payroll_exception.table.filters.assignment'))
                    ->placeholder(__('filament.resources.payroll_exception.table.filters.assignment_placeholder'))
                    ->trueLabel(__('filament.resources.payroll_exception.table.filters.assigned'))
                    ->falseLabel(__('filament.resources.payroll_exception.table.filters.unassigned'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('assigned_to'),
                        false: fn (Builder $query) => $query->whereNull('assigned_to'),
                        blank: fn (Builder $query) => $query,
                    ),
                Filter::make('overdue')
                    ->label(__('filament.resources.payroll_exception.table.filters.overdue'))
                    ->indicator(__('filament.resources.payroll_exception.table.filters.overdue'))
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->whereIn('status', ['open', 'in_review'])
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->striped()
            ->defaultSort('due_at', 'asc')
            ->emptyStateHeading(__('filament.resources.payroll_exception.table.empty_state.heading'))
            ->emptyStateDescription(__('filament.resources.payroll_exception.table.empty_state.description'))
            ->actions([
                Action::make('inspect')
                    ->label(__('filament.resources.payroll_exception.actions.inspect'))
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalHeading(__('filament.resources.payroll_exception.actions.inspect_modal_heading'))
                    ->modalWidth('4xl')
                    ->infolist([
                        InfolistSection::make(__('filament.resources.payroll_exception.dialogs.summary'))
                            ->schema([
                                TextEntry::make('payrollBatch.reference')
                                    ->label(__('filament.resources.payroll_exception.table.columns.batch')),
                                TextEntry::make('employee.external_id')
                                    ->label(__('filament.resources.payroll_exception.table.columns.employee')),
                                TextEntry::make('status')
                                    ->label(__('filament.resources.payroll_exception.table.columns.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'resolved' => 'success',
                                        'in_review' => 'warning',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => __('exceptions.statuses.' . $state)),
                                TextEntry::make('severity')
                                    ->label(__('filament.resources.payroll_exception.table.columns.severity'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'warning' => 'warning',
                                        'info' => 'info',
                                        default => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => Str::title($state)),
                                TextEntry::make('due_at')
                                    ->label(__('filament.resources.payroll_exception.form.fields.due_at'))
                                    ->dateTime(),
                                TextEntry::make('sla_status')
                                    ->label(__('exceptions.sla.label'))
                                    ->badge()
                                    ->color(fn (PayrollException $record): string => static::getSlaMeta($record)['color'])
                                    ->state(fn (PayrollException $record): string => static::getSlaMeta($record)['text']),
                                TextEntry::make('resolved_at')
                                    ->label(__('filament.resources.payroll_exception.form.fields.resolved_at'))
                                    ->dateTime(),
                                TextEntry::make('assigned_to')
                                    ->label(__('filament.resources.payroll_exception.table.columns.assignee'))
                                    ->formatStateUsing(fn (?string $state): string => $state ?? __('filament.resources.payroll_exception.table.columns.unassigned')),
                            ])->columns(2),
                        InfolistSection::make(__('filament.resources.payroll_exception.dialogs.messages'))
                            ->schema([
                                TextEntry::make('message.en')
                                    ->label(__('filament.resources.payroll_exception.dialogs.english'))
                                    ->columnSpanFull(),
                                TextEntry::make('message.ar')
                                    ->label(__('filament.resources.payroll_exception.dialogs.arabic'))
                                    ->columnSpanFull(),
                                TextEntry::make('context.value')
                                    ->label(__('filament.resources.payroll_exception.dialogs.context_value'))
                                    ->formatStateUsing(fn ($state) => is_scalar($state) ? (string) $state : json_encode($state)),
                                TextEntry::make('metadata.notes')
                                    ->label(__('filament.resources.payroll_exception.form.fields.notes'))
                                    ->columnSpanFull(),
                            ])->columns(2),
                        InfolistSection::make(__('exceptions.activity.heading'))
                            ->columns(1)
                            ->schema([
                                RepeatableEntry::make('activity')
                                    ->label(__('exceptions.activity.events'))
                                    ->state(fn (PayrollException $record) => static::getActivityFeed($record))
                                    ->schema(static::getActivityEntrySchema())
                                    ->columns(1)
                                    ->visible(fn (PayrollException $record) => filled(static::getActivityFeed($record))),
                                TextEntry::make('activity_placeholder')
                                    ->label('')
                                    ->state(fn () => __('exceptions.activity.empty'))
                                    ->visible(fn (PayrollException $record) => empty(static::getActivityFeed($record)))
                                    ->extraAttributes(['class' => 'text-sm text-gray-500 dark:text-gray-400'])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->form([
                        Section::make(__('filament.resources.payroll_exception.actions.update_assignment'))
                            ->schema([
                                Select::make('status')
                                    ->label(__('filament.resources.payroll_exception.table.columns.status'))
                                    ->options([
                                        'open' => __('exceptions.statuses.open'),
                                        'in_review' => __('exceptions.statuses.in_review'),
                                        'resolved' => __('exceptions.statuses.resolved'),
                                    ])
                                    ->required(),
                                TextInput::make('assigned_to')
                                    ->label(__('filament.resources.payroll_exception.table.columns.assignee'))
                                    ->maxLength(120),
                                DateTimePicker::make('due_at')
                                    ->label(__('filament.resources.payroll_exception.form.fields.due_at'))
                                    ->seconds(false)
                                    ->native(false),
                                Textarea::make('notes')
                                    ->label(__('filament.resources.payroll_exception.form.fields.notes'))
                                    ->columnSpanFull()
                                    ->rows(3),
                            ])
                            ->columns(2),
                    ])
                    ->fillForm(fn (PayrollException $record): array => [
                        'status' => $record->status,
                        'assigned_to' => $record->assigned_to,
                        'due_at' => $record->due_at,
                        'notes' => data_get($record->metadata, 'notes'),
                    ])
                    ->action(function (PayrollException $record, array $data): void {
                        $originalStatus = $record->status;
                        $metadata = $record->metadata ?? [];

                        if (blank($data['notes'] ?? null)) {
                            Arr::forget($metadata, 'notes');
                        } else {
                            Arr::set($metadata, 'notes', $data['notes']);
                        }

                        $record->fill([
                            'status' => $data['status'],
                            'assigned_to' => $data['assigned_to'] ?: null,
                            'due_at' => $data['due_at'] ?? null,
                            'metadata' => $metadata,
                        ]);

                        if ($data['status'] === 'resolved') {
                            if ($originalStatus !== 'resolved' && blank($record->resolved_at)) {
                                $record->resolved_at = now();
                            }
                        } elseif ($originalStatus === 'resolved') {
                            $record->resolved_at = null;
                        }

                        $record->save();
                    })
                    ->successNotificationTitle('Exception updated'),
                Tables\Actions\EditAction::make(),
                Action::make('markResolved')
                    ->label('Mark Resolved')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (PayrollException $record) => $record->status !== 'resolved')
                    ->requiresConfirmation()
                    ->action(fn (PayrollException $record) => $record->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollExceptions::route('/'),
            'view' => Pages\ViewPayrollException::route('/{record}'),
            'edit' => Pages\EditPayrollException::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['payrollBatch', 'employee']);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Summary')
                ->schema([
                    TextEntry::make('payrollBatch.reference')
                        ->label('Batch Reference'),
                    TextEntry::make('employee.external_id')
                        ->label('Employee External ID')
                        ->formatStateUsing(fn (?string $state) => $state ?? '—'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'resolved' => 'success',
                            'in_review' => 'warning',
                            default => 'danger',
                        })
                        ->formatStateUsing(fn (string $state) => Str::title(str_replace('_', ' ', $state))),
                    TextEntry::make('severity')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'error' => 'danger',
                            'warning' => 'warning',
                            default => 'info',
                        })
                        ->formatStateUsing(fn (string $state) => Str::title($state)),
                    TextEntry::make('due_at')
                        ->dateTime(),
                    TextEntry::make('sla_status')
                        ->label(__('exceptions.sla.label'))
                        ->badge()
                        ->color(fn (PayrollException $record): string => static::getSlaMeta($record)['color'])
                        ->state(fn (PayrollException $record): string => static::getSlaMeta($record)['text']),
                    TextEntry::make('resolved_at')
                        ->dateTime(),
                    TextEntry::make('assigned_to')
                        ->label('Assignee')
                        ->formatStateUsing(fn (?string $state) => $state ?? 'Unassigned'),
                ])->columns(2),
            InfolistSection::make('Messages')
                ->schema([
                    TextEntry::make('message.en')
                        ->label('English Message')
                        ->columnSpanFull(),
                    TextEntry::make('message.ar')
                        ->label('Arabic Message')
                        ->columnSpanFull(),
                    TextEntry::make('context.value')
                        ->label('Context Value')
                        ->formatStateUsing(fn ($state) => is_scalar($state) ? (string) $state : json_encode($state)),
                    TextEntry::make('metadata.notes')
                        ->label('Notes')
                        ->columnSpanFull(),
                ])->columns(2),
            InfolistSection::make(__('exceptions.activity.heading'))
                ->schema([
                    RepeatableEntry::make('activity')
                        ->label(__('exceptions.activity.events'))
                        ->state(fn (PayrollException $record) => static::getActivityFeed($record))
                        ->schema(static::getActivityEntrySchema())
                        ->columns(1)
                        ->visible(fn (PayrollException $record) => filled(static::getActivityFeed($record))),
                    TextEntry::make('activity_placeholder')
                        ->label('')
                        ->state(fn () => __('exceptions.activity.empty'))
                        ->visible(fn (PayrollException $record) => empty(static::getActivityFeed($record)))
                        ->extraAttributes(['class' => 'text-sm text-gray-500 dark:text-gray-400'])
                        ->columnSpanFull(),
                ])->columns(1),
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

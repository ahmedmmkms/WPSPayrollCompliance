<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollBatchResource\Pages;
use App\Models\PayrollBatch;
use App\Support\Sif\SifExportManager;
use App\Support\Sif\SifTemplateRepository;
use App\Support\Validation\BatchValidationManager;
use App\Support\Validation\RuleRepository;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

class PayrollBatchResource extends Resource
{
    protected static ?string $model = PayrollBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('filament.resources.payroll_batch.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.payroll_batch.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.resources.payroll_batch.plural_label');
    }

    protected static function batchStatusOptions(): array
    {
        return [
            'draft' => __('filament.common.status.draft'),
            'queued' => __('filament.common.status.queued'),
            'processing' => __('filament.common.status.processing'),
            'approved' => __('filament.common.status.approved'),
            'rejected' => __('filament.common.status.rejected'),
        ];
    }

    protected static function validationStatusLabel(?string $state): string
    {
        if ($state === null) {
            return __('filament.resources.payroll_batch.table.validation.pending');
        }

        return match ($state) {
            'passed' => __('filament.common.status.passed'),
            'failed' => __('filament.common.status.failed'),
            default => __('filament.common.status.pending'),
        };
    }

    protected static function exceptionBadgeLabel(?int $count): string
    {
        if ((int) $count === 0) {
            return __('filament.resources.payroll_batch.table.exceptions.clear');
        }

        return __('filament.resources.payroll_batch.table.exceptions.open', ['count' => (int) $count]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('filament.resources.payroll_batch.form.sections.details'))
                    ->schema([
                        Select::make('company_id')
                            ->label(__('filament.resources.payroll_batch.form.fields.company'))
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('reference')
                            ->label(__('filament.resources.payroll_batch.form.fields.reference'))
                            ->required()
                            ->maxLength(60)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament.resources.payroll_batch.form.fields.reference_helper')),
                        DateTimePicker::make('scheduled_for')
                            ->label(__('filament.resources.payroll_batch.form.fields.scheduled_for'))
                            ->seconds(false)
                            ->required()
                            ->helperText(__('filament.resources.payroll_batch.form.fields.scheduled_for_helper')),
                        Select::make('status')
                            ->label(__('filament.resources.payroll_batch.form.fields.status'))
                            ->options(static::batchStatusOptions())
                            ->default('draft')
                            ->required(),
                        Textarea::make('metadata.notes')
                            ->label(__('filament.resources.payroll_batch.form.fields.notes'))
                            ->maxLength(255)
                            ->rows(3)
                            ->helperText(__('filament.resources.payroll_batch.form.fields.notes_helper')),
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
                    ->label(__('filament.resources.payroll_batch.table.columns.company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reference')
                    ->label(__('filament.resources.payroll_batch.table.columns.reference'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('scheduled_for')
                    ->dateTime()
                    ->label(__('filament.resources.payroll_batch.table.columns.scheduled_for'))
                    ->sortable()
                    ->toggleable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'queued',
                        'primary' => 'processing',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->label(__('filament.resources.payroll_batch.table.columns.status'))
                    ->formatStateUsing(fn (string $state) => static::batchStatusOptions()[$state] ?? Str::title($state))
                    ->toggleable(),
                IconColumn::make('validation_status')
                    ->label(__('filament.resources.payroll_batch.table.columns.validation'))
                    ->state(fn (PayrollBatch $record) => data_get($record->metadata, 'validation.status'))
                    ->colors([
                        'success' => 'passed',
                        'danger' => 'failed',
                        'warning' => fn (?string $state) => blank($state) || $state === 'pending',
                    ])
                    ->icon(fn (?string $state) => match ($state) {
                        'passed' => 'heroicon-m-check-circle',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-clock',
                    })
                    ->tooltip(fn (PayrollBatch $record) => static::validationStatusLabel(data_get($record->metadata, 'validation.status')))
                    ->toggleable(),
                BadgeColumn::make('open_exceptions_count')
                    ->label(__('filament.resources.payroll_batch.table.columns.exceptions'))
                    ->icon('heroicon-m-exclamation-triangle')
                    ->colors([
                        'success' => fn (?int $state) => (int) $state === 0,
                        'danger' => fn (?int $state) => (int) $state > 0,
                    ])
                    ->formatStateUsing(fn (?int $state) => static::exceptionBadgeLabel($state))
                    ->tooltip(fn (PayrollBatch $record) => $record->open_exceptions_count > 0
                        ? __('filament.common.messages.open_exceptions_tooltip')
                        : __('filament.common.messages.no_exceptions_tooltip'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('filament.resources.payroll_batch.table.columns.created_at'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('filament.resources.payroll_batch.table.columns.updated_at'))
                    ->sortable()
                    ->toggleable(isHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_for', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->multiple()
                    ->label(__('filament.resources.payroll_batch.table.columns.status'))
                    ->options(static::batchStatusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('runValidation')
                    ->label(__('filament.resources.payroll_batch.actions.run_validation'))
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->form([
                        Select::make('rule_sets')
                            ->label(__('filament.resources.payroll_batch.form.fields.rule_sets'))
                            ->options(function () {
                                $locale = app()->getLocale();

                                return collect(app(RuleRepository::class)->all())
                                    ->mapWithKeys(function ($ruleSet) use ($locale) {
                                        $label = $ruleSet->name[$locale] ?? $ruleSet->name['en'] ?? $ruleSet->id;

                                        return [$ruleSet->id => $label];
                                    })
                                    ->sort()
                                    ->all();
                            })
                            ->default(config('validation.default_sets'))
                            ->required()
                            ->multiple()
                            ->preload(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PayrollBatch $record, array $data) {
                        $selectedRuleSets = array_values(array_filter($data['rule_sets'] ?? []));

                        if (empty($selectedRuleSets)) {
                            Notification::make()
                                ->title(__('filament.resources.payroll_batch.notifications.select_rule_set'))
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            $report = app(BatchValidationManager::class)->run($record, $selectedRuleSets);
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title(__('filament.resources.payroll_batch.notifications.failed'))
                                ->danger()
                                ->body($exception->getMessage())
                                ->send();

                            return;
                        }

                        $failures = $report->failures()->count();
                        $total = $report->results()->count();

                        $notification = Notification::make()
                            ->title($failures === 0
                                ? __('filament.resources.payroll_batch.notifications.passed_title')
                                : __('filament.resources.payroll_batch.notifications.issues_title'))
                            ->body($failures === 0
                                ? __('filament.resources.payroll_batch.notifications.passed_body', ['total' => $total])
                                : __('filament.resources.payroll_batch.notifications.issues_body', ['failures' => $failures, 'total' => $total]));

                        if ($failures === 0) {
                            $notification->success();
                        } else {
                            $notification->danger();
                        }

                        $notification->send();
                    }),
                Action::make('viewValidation')
                    ->label(__('filament.resources.payroll_batch.actions.view_validation'))
                    ->icon('heroicon-m-clipboard-document-list')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->hidden(fn (PayrollBatch $record) => blank(data_get($record->metadata, 'validation')))
                    ->infolist([
                        InfolistSection::make(__('filament.resources.payroll_batch.dialogs.validation.summary_heading'))
                            ->schema([
                                TextEntry::make('status')
                                    ->label(__('filament.resources.payroll_batch.dialogs.validation.status'))
                                    ->state(fn (PayrollBatch $record) => Str::headline((string) data_get($record->metadata, 'validation.status')))
                                    ->badge()
                                    ->color(fn (PayrollBatch $record) => match (data_get($record->metadata, 'validation.status')) {
                                        'passed' => 'success',
                                        'failed' => 'danger',
                                        default => 'warning',
                                    }),
                                TextEntry::make('ran_at')
                                    ->label(__('filament.resources.payroll_batch.dialogs.validation.validated_at'))
                                    ->state(fn (PayrollBatch $record) => data_get($record->metadata, 'validation.ran_at')),
                                TextEntry::make('totals')
                                    ->label(__('filament.resources.payroll_batch.dialogs.validation.results'))
                                    ->state(function (PayrollBatch $record) {
                                        $summary = data_get($record->metadata, 'validation.summary', []);

                                        return __('filament.resources.payroll_batch.dialogs.validation.checks', [
                                            'total' => (int) ($summary['total'] ?? 0),
                                            'passes' => (int) ($summary['passes'] ?? 0),
                                            'failures' => (int) ($summary['failures'] ?? 0),
                                        ]);
                                    }),
                            ])
                            ->columns(1),
                        InfolistSection::make(__('filament.resources.payroll_batch.dialogs.validation.rule_checks'))
                            ->schema([
                                RepeatableEntry::make('results')
                                    ->state(fn (PayrollBatch $record) => data_get($record->metadata, 'validation.results', []))
                                    ->columns(1)
                                    ->schema([
                                        TextEntry::make('rule_id')
                                            ->label(__('filament.resources.payroll_batch.dialogs.validation.rule'))
                                            ->state(fn (array $state) => $state['rule_id'] ?? ''),
                                        TextEntry::make('severity')
                                            ->label(__('filament.resources.payroll_batch.dialogs.validation.severity'))
                                            ->state(fn (array $state) => Str::headline((string) ($state['severity'] ?? '')))
                                            ->badge()
                                            ->color(fn (array $state) => match ($state['severity'] ?? 'error') {
                                                'warning' => 'warning',
                                                'info' => 'info',
                                                default => 'danger',
                                            }),
                                        TextEntry::make('result')
                                            ->label(__('filament.resources.payroll_batch.dialogs.validation.result'))
                                            ->state(fn (array $state) => ($state['passed'] ?? false)
                                                ? __('filament.common.status.passed')
                                                : __('filament.common.status.failed'))
                                            ->badge()
                                            ->color(fn (array $state) => ($state['passed'] ?? false) ? 'success' : 'danger'),
                                        TextEntry::make('message')
                                            ->label(__('filament.resources.payroll_batch.dialogs.validation.message'))
                                            ->state(function (array $state) {
                                                $message = $state['message'] ?? '';

                                                if (is_array($message)) {
                                                    $locale = app()->getLocale();

                                                    return $message[$locale] ?? $message['en'] ?? collect($message)->first() ?? '';
                                                }

                                                return (string) $message;
                                            }),
                                    ]),
                            ]),
                    ]),
                Action::make('queueSifExport')
                    ->label(__('filament.resources.payroll_batch.actions.queue_sif_export'))
                    ->icon('heroicon-m-arrow-down-on-square-stack')
                    ->color('primary')
                    ->form([
                        Select::make('template_key')
                            ->label(__('filament.resources.payroll_batch.form.fields.template'))
                            ->options(function () {
                                $locale = app()->getLocale();

                                return collect(app(SifTemplateRepository::class)->all())
                                    ->mapWithKeys(function ($template) use ($locale) {
                                        $label = $template->labels[$locale] ?? $template->labels['en'] ?? $template->key;

                                        return [$template->key => $label];
                                    })
                                    ->all();
                            })
                            ->default(config('sif.default_template'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PayrollBatch $record, array $data) {
                        try {
                            $entry = app(SifExportManager::class)->queue($record, $data['template_key']);
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title(__('filament.resources.payroll_batch.notifications.export_failed'))
                                ->danger()
                                ->body($exception->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('filament.resources.payroll_batch.notifications.export_queued'))
                            ->body(__('filament.resources.payroll_batch.notifications.export_body', [
                                'template' => $entry['template_label'] ?? $entry['template'],
                            ]))
                            ->success()
                            ->send();
                    }),
                Action::make('viewExportHistory')
                    ->label(__('filament.resources.payroll_batch.actions.view_export_history'))
                    ->icon('heroicon-m-clock')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->hidden(fn (PayrollBatch $record) => empty(data_get($record->metadata, 'exports')))
                    ->infolist([
                        InfolistSection::make(__('filament.resources.payroll_batch.dialogs.exports.heading'))
                            ->schema([
                                RepeatableEntry::make('exports')
                                    ->state(fn (PayrollBatch $record) => data_get($record->metadata, 'exports', []))
                                    ->columns(1)
                                    ->schema([
                                        TextEntry::make('template_label')
                                            ->label(__('filament.resources.payroll_batch.dialogs.exports.template'))
                                            ->state(fn (array $state) => $state['template_label'] ?? $state['template'] ?? ''),
                                        TextEntry::make('status')
                                            ->label(__('filament.resources.payroll_batch.dialogs.validation.status'))
                                            ->state(fn (array $state) => __('filament.resources.payroll_batch.dialogs.exports.status.' . (($state['status'] ?? 'queued'))))
                                            ->badge()
                                            ->color(fn (array $state) => match ($state['status'] ?? 'queued') {
                                                'available' => 'success',
                                                'failed' => 'danger',
                                                default => 'warning',
                                            }),
                                        TextEntry::make('queued_at')
                                            ->label(__('filament.resources.payroll_batch.dialogs.exports.queued_at'))
                                            ->state(fn (array $state) => $state['queued_at'] ?? null),
                                        TextEntry::make('available_at')
                                            ->label(__('filament.resources.payroll_batch.dialogs.exports.available_at'))
                                            ->state(fn (array $state) => $state['available_at'] ?? '—'),
                                        TextEntry::make('download_url')
                                            ->label(__('filament.resources.payroll_batch.dialogs.exports.download_url'))
                                            ->state(fn (array $state) => $state['download_url'] ?? __('filament.resources.payroll_batch.dialogs.exports.pending')),
                                    ]),
                            ]),
                    ]),
                Action::make('viewAuditTrail')
                    ->label(__('filament.resources.payroll_batch.actions.view_audit_trail'))
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->hidden(fn (PayrollBatch $record) => empty(data_get($record->metadata, 'audit.trail')))
                    ->infolist([
                        InfolistSection::make(__('filament.resources.payroll_batch.dialogs.audit.heading'))
                            ->schema([
                                RepeatableEntry::make('audit.trail')
                                    ->label(__('filament.resources.payroll_batch.dialogs.audit.event'))
                                    ->state(fn (PayrollBatch $record) => array_reverse(data_get($record->metadata, 'audit.trail', [])))
                                    ->schema([
                                        TextEntry::make('event')
                                            ->label(__('filament.resources.payroll_batch.dialogs.audit.event'))
                                            ->state(fn (array $state) => Str::headline(str_replace('.', ' ', $state['event'] ?? '')))
                                            ->badge()
                                            ->color(fn (array $state) => match ($state['event'] ?? '') {
                                                'validation.run' => 'info',
                                                'sif.queued' => 'warning',
                                                'sif.generated' => 'success',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('occurred_at')
                                            ->label(__('filament.resources.payroll_batch.dialogs.audit.occurred_at'))
                                            ->state(fn (array $state) => $state['occurred_at'] ?? '')
                                            ->dateTime(),
                                        TextEntry::make('payload.summary.failures')
                                            ->label(__('filament.resources.payroll_batch.dialogs.audit.failures'))
                                            ->hidden(fn (array $state) => ! isset($state['payload']['summary']['failures'])),
                                        TextEntry::make('payload.template')
                                            ->label(__('filament.resources.payroll_batch.dialogs.audit.template'))
                                            ->hidden(fn (array $state) => ! isset($state['payload']['template'])),
                                    ])
                                    ->columns(1),
                            ]),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('filament.resources.payroll_batch.table.empty_state.heading'))
            ->emptyStateDescription(__('filament.resources.payroll_batch.table.empty_state.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('filament.resources.payroll_batch.table.empty_state.action')),
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
            'index' => Pages\ListPayrollBatches::route('/'),
            'create' => Pages\CreatePayrollBatch::route('/create'),
            'edit' => Pages\EditPayrollBatch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('company')
            ->withCount([
                'exceptions as open_exceptions_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'in_review']),
            ]);
    }
}

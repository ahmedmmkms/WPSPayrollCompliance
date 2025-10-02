<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Jobs\ImportEmployees;
use App\Models\Company;
use App\Support\EmployeeImport;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use Throwable;

use function Stancl\Tenancy\tenant;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    public array $importPreview = [];

    public array $importHeaders = [];

    public ?string $importError = null;

    protected function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label(__('filament.resources.employee.table.empty_state.action')),
            Action::make('import')
                ->label(__('filament.resources.employee.import.label'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->modalWidth('xl')
                ->modalSubmitActionLabel(__('filament.resources.employee.import.submit'))
                ->form([
                    Select::make('company_id')
                        ->label(__('filament.resources.employee.import.fields.company'))
                        ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->helperText(__('filament.resources.employee.import.helper')),
                    FileUpload::make('import_file')
                        ->label(__('filament.resources.employee.import.fields.file'))
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required()
                        ->storeFiles(false)
                        ->directory('tmp')
                        ->reactive()
                        ->afterStateUpdated(fn (?TemporaryUploadedFile $state) => $this->buildPreview($state)),
                    Fieldset::make(__('filament.resources.employee.import.preview.heading'))
                        ->schema([
                            Placeholder::make('import_error')
                                ->hiddenLabel()
                                ->visible(fn () => filled($this->importError))
                                ->content(fn () => $this->importError)
                                ->extraAttributes([
                                    'class' => 'text-sm text-rose-500 bg-rose-50 dark:bg-rose-950/40 rounded-md px-3 py-2',
                                ]),
                            ViewComponent::make('import_preview')
                                ->visible(fn () => blank($this->importError) && ! empty($this->importPreview))
                                ->view('filament.employees.import-preview')
                                ->viewData([
                                    'headers' => fn () => $this->importHeaders,
                                    'rows' => fn () => $this->importPreview,
                                ]),
                        ])
                        ->columns(1),
                ])
                ->action(function (array $data): void {
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['import_file'];

                    if ($this->importError) {
                        Notification::make()
                            ->title(__('filament.resources.employee.import.notifications.resolve'))
                            ->danger()
                            ->body($this->importError)
                            ->send();

                        return;
                    }

                    if (empty($this->importHeaders)) {
                        Notification::make()
                            ->title(__('filament.resources.employee.import.notifications.columns_title'))
                            ->danger()
                            ->body(__('filament.resources.employee.import.notifications.columns_body', [
                                'columns' => implode(', ', EmployeeImport::REQUIRED_HEADERS),
                            ]))
                            ->send();

                        return;
                    }

                    $tenantId = tenant()?->getTenantKey();

                    $directory = 'imports/'.($tenantId ?? 'central');
                    $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

                    $path = $file->storeAs($directory, $filename, 'local');

                    ImportEmployees::dispatch(
                        tenantId: $tenantId,
                        path: $path,
                        companyId: $data['company_id'] ?? null,
                    );

                    Notification::make()
                        ->title(__('filament.resources.employee.import.notifications.queued_title'))
                        ->success()
                        ->body(__('filament.resources.employee.import.notifications.queued_body'))
                        ->send();

                    $this->resetImportState();
                }),
        ];
    }

    protected function buildPreview(?TemporaryUploadedFile $file): void
    {
        $this->resetImportState();

        if (! $file) {
            return;
        }

        try {
            $reader = $this->makePreviewReader($file);
            $reader->open($file->getRealPath());

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = $row->toArray();

                    if ($rowIndex === 1) {
                        $this->importHeaders = EmployeeImport::normalizeHeaders($cells);
                        EmployeeImport::assertHasRequiredHeaders($this->importHeaders);

                        continue;
                    }

                    if (empty($this->importHeaders)) {
                        continue;
                    }

                    $mapped = EmployeeImport::mapRow($this->importHeaders, $cells);

                    if (! array_filter($mapped)) {
                        continue;
                    }

                    $this->importPreview[] = $mapped;

                    if (count($this->importPreview) >= 5) {
                        break 2;
                    }
                }
            }

            $reader->close();

            if (empty($this->importPreview)) {
                $this->importError = __('filament.resources.employee.import.preview.empty');
            }
        } catch (Throwable $exception) {
            $this->importError = $exception->getMessage();
        }
    }

    protected function resetImportState(): void
    {
        $this->importPreview = [];
        $this->importHeaders = [];
        $this->importError = null;
    }

    protected function makePreviewReader(TemporaryUploadedFile $file)
    {
        return match (strtolower($file->getClientOriginalExtension() ?? 'csv')) {
            'xlsx' => ReaderEntityFactory::createXLSXReader(),
            'ods' => ReaderEntityFactory::createODSReader(),
            default => ReaderEntityFactory::createCSVReader(),
        };
    }
}

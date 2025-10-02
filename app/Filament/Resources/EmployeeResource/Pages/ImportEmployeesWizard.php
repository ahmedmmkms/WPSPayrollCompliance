<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Jobs\ImportEmployees;
use App\Models\Company;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ImportEmployeesWizard extends CreateRecord
{
    protected static string $resource = \App\Filament\Resources\EmployeeResource::class;

    protected static ?string $title = 'Import Employees';

    protected ?string $heading = 'Import Employee Data';

    protected static string $view = 'filament.resources.employee-resource.pages.import-employees-wizard';

    public ?array $data = [];

    public ?array $columnMap = [];

    public ?array $previewData = [];

    public int $step = 1;

    public $upload = null;

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->data = $this->form->getState();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Import Settings')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->visible($this->step === 1),
                        FileUpload::make('upload')
                            ->label('Upload Employee File')
                            ->helperText('Upload a CSV or Excel file containing employee information. Download the sample template to match required format.')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->preserveFilenames()
                            ->required()
                            ->visible($this->step === 1),
                        Repeater::make('column_map')
                            ->label('Map Columns')
                            ->helperText('Match the columns in your file to the employee properties')
                            ->schema([
                                TextInput::make('column_name')
                                    ->label('File Column')
                                    ->readOnly(),
                                Select::make('mapped_field')
                                    ->label('Map To')
                                    ->options([
                                        'first_name' => 'First Name',
                                        'last_name' => 'Last Name',
                                        'email' => 'Email',
                                        'phone' => 'Phone',
                                        'salary' => 'Salary',
                                        'currency' => 'Currency',
                                    ])
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->visible($this->step === 2),
                        Repeater::make('preview_data')
                            ->label('Data Preview')
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->readOnly(),
                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->readOnly(),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->readOnly(),
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->readOnly(),
                                TextInput::make('salary')
                                    ->label('Salary')
                                    ->readOnly(),
                                TextInput::make('currency')
                                    ->label('Currency')
                                    ->readOnly(),
                            ])
                            ->hidden()
                            ->visible($this->step === 2),
                    ])
            ])
            ->columns(1);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            // Validate step 1 data
            $this->validate([
                'data.company_id' => 'required|exists:companies,id',
                'upload' => 'required',
            ]);
            
            // Process the uploaded file
            if ($this->upload) {
                $this->prepareColumnMapping();
            }
        } elseif ($this->step === 2) {
            // Validate the column mapping
            if (empty($this->columnMap)) {
                Notification::make()
                    ->title('Column mapping required')
                    ->body('Please map all required columns before proceeding')
                    ->danger()
                    ->send();
                return;
            }
            
            $this->step = 3;
        }
    }

    public function previousStep(): void
    {
        $this->step--;
    }

    private function prepareColumnMapping(): void
    {
        $filepath = Storage::disk('local')->path('app/' . $this->upload);
        
        if (!file_exists($filepath)) {
            Notification::make()
                ->title('File not found')
                ->body('The uploaded file could not be found')
                ->danger()
                ->send();
            return;
        }

        // Read the first few rows to extract headers and sample data
        $headers = [];
        $sampleRows = [];
        
        if (($handle = fopen($filepath, "r")) !== false) {
            $rowIndex = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== false && $rowIndex < 5) {
                if ($rowIndex === 0) {
                    // First row contains headers
                    $headers = $data;
                } else {
                    // Add sample row with column mapping
                    $sampleRows[$rowIndex-1] = array_combine($headers, $data);
                }
                $rowIndex++;
            }
            fclose($handle);
        }

        // Prepare column mapping with common field suggestions
        $this->columnMap = [];
        foreach ($headers as $header) {
            $mappedField = $this->suggestMappedField(strtolower($header));
            $this->columnMap[] = [
                'column_name' => $header,
                'mapped_field' => $mappedField,
            ];
        }

        // Prepare preview data
        $this->previewData = [];
        foreach ($sampleRows as $row) {
            $previewRow = [];
            foreach ($this->columnMap as $mapping) {
                $previewRow[$mapping['mapped_field']] = $row[$mapping['column_name']] ?? '';
            }
            $this->previewData[] = $previewRow;
        }

        $this->step = 2;
    }

    private function suggestMappedField(string $header): string
    {
        $fieldSuggestions = [
            'first_name' => ['first name', 'firstname', 'first', 'given name', 'givenname'],
            'last_name' => ['last name', 'lastname', 'last', 'surname', 'family name', 'familyname'],
            'email' => ['email', 'email address', 'e-mail', 'e mail', 'emailaddress'],
            'phone' => ['phone', 'phone number', 'phone no', 'telephone', 'mobile', 'cell', 'contact'],
            'salary' => ['salary', 'wage', 'amount', 'pay'],
            'currency' => ['currency', 'currency code', 'currency_code', 'ccy'],
        ];

        foreach ($fieldSuggestions as $field => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($header, $pattern)) {
                    return $field;
                }
            }
        }

        return 'skip'; // Default action is to skip unmapped columns
    }

    public function startImport(): void
    {
        // Validate final mapping
        $requiredFields = ['first_name', 'last_name', 'email'];
        $mappedFields = array_column($this->columnMap, 'mapped_field');
        
        foreach ($requiredFields as $field) {
            if (!in_array($field, $mappedFields)) {
                Notification::make()
                    ->title("Missing required mapping: {$field}")
                    ->body("Please map a column to the {$field} field before importing")
                    ->danger()
                    ->send();
                return;
            }
        }

        // Dispatch the import job
        $tenantId = tenant()->id;
        ImportEmployees::dispatch($tenantId, $this->upload, $this->data['company_id']);

        Notification::make()
            ->title('Import Started')
            ->body('Your employee import has started and is running in the background')
            ->success()
            ->send();

        // Redirect back to employee list
        $this->redirect($this->getResource()::getUrl('index'));
    }

    public function getSteps(): array
    {
        return [
            'Upload File' => 'Upload your employee data file',
            'Map Columns' => 'Map file columns to employee properties',
            'Review & Import' => 'Confirm mapping and start import',
        ];
    }

    public function getStepTitle(): string
    {
        return array_keys($this->getSteps())[$this->step - 1];
    }

    public function getStepDescription(): string
    {
        return array_values($this->getSteps())[$this->step - 1];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
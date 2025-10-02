<?php

namespace App\Jobs;

use App\Events\EmployeesImported;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Tenant;
use App\Support\EmployeeImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use RuntimeException;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class ImportEmployees implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $path,
        public ?string $companyId = null,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($this->path)) {
            return;
        }

        $absolutePath = $disk->path($this->path);

        $imported = 0;
        $skipped = 0;
        $headers = [];

        Tenancy::initialize($tenant);

        try {
            $rowIndex = 0;

            foreach ($this->iterateImportRows($absolutePath) as $cells) {
                $rowIndex++;

                if ($rowIndex === 1) {
                    $headers = EmployeeImport::normalizeHeaders($cells);
                    EmployeeImport::assertHasRequiredHeaders($headers);

                    continue;
                }

                if (! $headers) {
                    continue;
                }

                $payload = EmployeeImport::mapRow($headers, $cells);

                $companyId = $this->companyId ?: ($payload['company_id'] ?? null);

                if (! $companyId) {
                    $skipped++;

                    continue;
                }

                if (! Company::query()->whereKey($companyId)->exists()) {
                    $skipped++;

                    continue;
                }

                Employee::updateOrCreate([
                    'company_id' => $companyId,
                    'external_id' => $payload['external_id'] ?? null,
                ], [
                    'first_name' => $payload['first_name'] ?? null,
                    'last_name' => $payload['last_name'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'salary' => (float) ($payload['salary'] ?? 0),
                    'currency' => $payload['currency'] ?? 'AED',
                    'metadata' => $payload,
                ]);

                $imported++;
            }
        } catch (Throwable $exception) {
            Log::error('Employee import failed', [
                'tenant_id' => $tenant->getKey(),
                'path' => $this->path,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            Tenancy::end();
            $disk->delete($this->path);
        }

        Log::info('Employees import completed', [
            'tenant_id' => $tenant->getKey(),
            'path' => $this->path,
            'imported' => $imported,
            'skipped' => $skipped,
            'company_id' => $this->companyId,
        ]);

        event(new EmployeesImported(
            tenantId: $tenant->getKey(),
            imported: $imported,
            skipped: $skipped,
            companyId: $this->companyId,
        ));
    }

    /**
     * @return iterable<int, array<int, string|null>>
     */
    private function iterateImportRows(string $absolutePath): iterable
    {
        if (class_exists(ReaderEntityFactory::class)) {
            $reader = ReaderEntityFactory::createReaderFromFile($absolutePath);
            $reader->open($absolutePath);

            try {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        yield $row->toArray();
                    }
                }
            } finally {
                $reader->close();
            }

            return;
        }

        $handle = fopen($absolutePath, 'rb');

        if (! $handle) {
            throw new RuntimeException('Unable to open import file for reading.');
        }

        try {
            while (($cells = fgetcsv($handle)) !== false) {
                $hasContent = collect($cells)
                    ->filter(static fn ($value) => ! is_null($value) && trim((string) $value) !== '')
                    ->isNotEmpty();

                if (! $hasContent) {
                    continue;
                }

                yield $cells;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'tenant:'.$this->tenantId,
            'company:'.($this->companyId ?? 'auto'),
        ];
    }
}

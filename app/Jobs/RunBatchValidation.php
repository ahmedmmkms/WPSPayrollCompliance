<?php

namespace App\Jobs;

use App\Models\PayrollBatch;
use App\Models\Tenant;
use App\Support\Validation\BatchValidationManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Facades\Tenancy;

class RunBatchValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>|null  $ruleSets
     */
    public function __construct(
        public string $tenantId,
        public string $batchId,
        public ?array $ruleSets = null,
    ) {
        $this->onQueue('validation');
    }

    public function handle(BatchValidationManager $manager): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        Tenancy::initialize($tenant);

        try {
            $batch = PayrollBatch::query()->find($this->batchId);

            if (! $batch) {
                return;
            }

            $manager->run($batch, $this->ruleSets);
        } finally {
            Tenancy::end();
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'tenant:'.$this->tenantId,
            'batch:'.$this->batchId,
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class DeleteTenant extends Command
{
    protected $signature = 'tenants:delete
        {tenant : Tenant ID or domain to delete}
        {--force : Skip confirmation prompt}';

    protected $description = 'Delete a tenant and all associated domains.';

    public function handle(): int
    {
        $identifier = $this->argument('tenant');

        $tenant = Tenant::query()
            ->where('id', $identifier)
            ->orWhereHas('domains', fn ($query) => $query->where('domain', $identifier))
            ->first();

        if (! $tenant) {
            $this->error("Tenant '{$identifier}' not found.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete tenant {$tenant->id}? This will remove all tenant domains and data.")) {
            $this->comment('Aborting.');

            return self::INVALID;
        }

        $tenant->delete();

        $this->info("Tenant {$tenant->id} deleted.");

        return self::SUCCESS;
    }
}

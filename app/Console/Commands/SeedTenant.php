<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\Tenant\TenantSeeder;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SeedTenant extends Command
{
    protected $signature = 'tenants:seed
        {tenant? : Tenant ID or domain. Leave empty to seed all tenants}';

    protected $description = 'Run tenant database seeders for one or all tenants.';

    public function handle(): int
    {
        $identifier = $this->argument('tenant');

        $tenants = Tenant::query()
            ->when($identifier, function ($query, $identifier) {
                $query->where('id', $identifier)
                    ->orWhereHas('domains', fn ($q) => $q->where('domain', $identifier));
            })
            ->get();

        if ($tenants->isEmpty()) {
            $this->error($identifier ? "Tenant '{$identifier}' not found." : 'No tenants available to seed.');

            return self::FAILURE;
        }

        $tenants->each(function (Tenant $tenant) {
            Tenancy::initialize($tenant);

            app(TenantSeeder::class)->run();

            Tenancy::end();

            $this->info("Seeded tenant {$tenant->id}.");
        });

        return self::SUCCESS;
    }
}

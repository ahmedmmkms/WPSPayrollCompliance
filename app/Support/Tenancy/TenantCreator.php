<?php

namespace App\Support\Tenancy;

use App\Models\Company;
use App\Models\Tenant;
use Database\Seeders\Tenant\TenantSeeder;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class TenantCreator
{
    public function create(
        string $name,
        string $domain,
        ?string $email = null,
        ?string $tenantId = null,
        bool $seed = true,
    ): Tenant {
        $tenant = $this->findTenantByDomain($domain);
        $created = false;

        if (! $tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId ?: (string) Str::uuid(),
                'data' => array_filter([
                    'company_name' => $name,
                    'contact_email' => $email,
                ]),
            ]);
            $created = true;
        } else {
            $tenant->forceFill([
                'data' => array_merge(
                    $tenant->data ?? [],
                    array_filter([
                        'company_name' => $name,
                        'contact_email' => $email,
                    ]),
                ),
            ])->save();
        }

        if (! $tenant->domains()->where('domain', $domain)->exists()) {
            $tenant->domains()->create([
                'domain' => $domain,
            ]);
            $created = true;
        }

        $this->initializeTenant($tenant, $name, $email, $seed && $created);

        return $tenant;
    }

    public function domainExists(string $domain): bool
    {
        return $this->findTenantByDomain($domain) !== null;
    }

    protected function findTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::query()
            ->whereHas('domains', function ($query) use ($domain) {
                $query->where('domain', $domain);
            })
            ->first();
    }

    protected function initializeTenant(Tenant $tenant, string $name, ?string $email, bool $seed): void
    {
        Tenancy::initialize($tenant);

        try {
            Company::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'name' => $name,
                    'trade_license' => null,
                    'contact_email' => $email,
                ],
            );

            if ($seed) {
                app(TenantSeeder::class)->run();
            }
        } finally {
            Tenancy::end();
        }
    }
}

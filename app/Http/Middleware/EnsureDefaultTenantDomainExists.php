<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantCreator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsureDefaultTenantDomainExists
{
    public function __construct(private TenantCreator $tenantCreator) {}

    public function handle(Request $request, Closure $next)
    {
        $config = config('tenancy.default_tenant');

        if (! is_array($config)) {
            return $next($request);
        }

        $domain = $this->normalizeDomain($config['domain'] ?? null);

        if (! $domain) {
            $domain = $this->normalizeDomain($request->getHost());
        }

        if (! $domain || ! $this->shouldProvision($request, $domain)) {
            return $next($request);
        }

        $name = $config['name'] ?? 'Default Tenant';
        $email = $config['email'] ?? null;
        $tenantId = $config['id'] ?? null;
        $seed = $this->interpretBoolean($config['seed'] ?? true);

        $this->tenantCreator->create(
            name: $name,
            domain: $domain,
            email: $email,
            tenantId: $tenantId,
            seed: $seed,
        );

        return $next($request);
    }

    protected function shouldProvision(Request $request, string $domain): bool
    {
        if (! $this->tablesExist()) {
            return false;
        }

        if ($request->getHost() !== $domain) {
            return false;
        }

        if ($this->isCentralDomain($domain)) {
            return false;
        }

        if ($this->tenantCreator->domainExists($domain)) {
            return false;
        }

        return true;
    }

    protected function isCentralDomain(string $domain): bool
    {
        $centralDomains = collect(config('tenancy.central_domains', []))
            ->filter()
            ->map(fn ($value) => $this->normalizeDomain($value))
            ->filter();

        return $centralDomains->contains($domain);
    }

    protected function tablesExist(): bool
    {
        $connection = config('tenancy.database.central_connection')
            ?: config('database.default');

        return rescue(
            function () use ($connection) {
                $schema = Schema::connection($connection);

                return $schema->hasTable('tenants') && $schema->hasTable('domains');
            },
            false,
            report: false,
        );
    }

    protected function normalizeDomain(?string $domain): ?string
    {
        if (! $domain) {
            return null;
        }

        $domain = strtolower(trim($domain));

        return parse_url($domain, PHP_URL_HOST) ?: $domain;
    }

    protected function interpretBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }
}

<?php

namespace App\Console\Commands;

use App\Support\Tenancy\TenantCreator;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    protected $signature = 'tenants:create
        {--name= : Display name for the tenant/company}
        {--domain= : Primary domain (e.g. acme.example.com)}
        {--email= : Contact email for the tenant}
        {--skip-seed : Do not run tenant seeders after creation}';

    protected $description = 'Create a new tenant with default company metadata and optional seeders.';

    public function __construct(protected TenantCreator $tenantCreator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Tenant name');
        $domain = $this->option('domain') ?: $this->ask('Primary domain (e.g. acme.example.com)');
        $email = $this->option('email') ?: $this->ask('Contact email');

        if (! $name || ! $domain) {
            $this->error('Name and domain are required.');
            return self::INVALID;
        }

        $domain = strtolower(trim($domain));
        $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;

        if ($this->tenantCreator->domainExists($domain)) {
            $this->error("A tenant already exists for domain {$domain}.");
            return self::FAILURE;
        }

        $tenant = $this->tenantCreator->create(
            name: $name,
            domain: $domain,
            email: $email,
            tenantId: null,
            seed: ! (bool) $this->option('skip-seed'),
        );

        $this->info("Tenant {$tenant->id} created with domain {$domain}.");

        return self::SUCCESS;
    }
}

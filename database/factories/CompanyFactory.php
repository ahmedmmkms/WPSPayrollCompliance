<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'tenant_id' => tenant()?->getTenantKey() ?? Str::uuid()->toString(),
            'name' => $this->faker->company(),
            'trade_license' => strtoupper($this->faker->bothify('TL-#####')),
            'contact_email' => $this->faker->companyEmail(),
            'metadata' => [
                'contact_phone' => $this->faker->phoneNumber(),
            ],
        ];
    }
}

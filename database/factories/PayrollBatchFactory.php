<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PayrollBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PayrollBatch>
 */
class PayrollBatchFactory extends Factory
{
    protected $model = PayrollBatch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'reference' => strtoupper(Str::random(12)),
            'scheduled_for' => $this->faker->dateTimeBetween('+1 day', '+2 weeks'),
            'status' => $this->faker->randomElement(['draft', 'queued', 'processing']),
            'metadata' => [],
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PayrollException>
 */
class PayrollExceptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_batch_id' => PayrollBatch::factory(),
            'employee_id' => Employee::factory(),
            'rule_id' => fake()->unique()->regexify('rule-[0-9]{4}'),
            'rule_set_id' => fake()->randomElement(['uae-wps-v1', 'ksa-mudad-sandbox']),
            'severity' => fake()->randomElement(['error', 'warning']),
            'status' => fake()->randomElement(['open', 'in_review', 'resolved']),
            'origin' => 'validation',
            'assigned_to' => fake()->name(),
            'due_at' => now()->addDays(2),
            'resolved_at' => null,
            'message' => [
                'en' => fake()->sentence(),
            ],
            'context' => [
                'value' => fake()->randomFloat(2, 0, 10000),
            ],
            'metadata' => [
                'last_notified_at' => now()->toISOString(),
            ],
        ];
    }
}

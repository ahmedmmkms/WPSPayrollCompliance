<?php

namespace Database\Factories;

use App\Models\ExceptionNotification;
use App\Models\PayrollException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExceptionNotification>
 */
class ExceptionNotificationFactory extends Factory
{
    protected $model = ExceptionNotification::class;

    public function definition(): array
    {
        return [
            'payroll_exception_id' => PayrollException::factory(),
            'type' => $this->faker->randomElement(['status_changed', 'assignment_changed']),
            'locale' => $this->faker->randomElement(['en', 'ar']),
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->sentence(),
            'payload' => [
                'demo' => true,
            ],
            'queued_at' => now(),
            'sent_at' => null,
        ];
    }
}

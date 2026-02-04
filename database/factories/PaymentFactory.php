<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'month' => now()->format('Y-m'),
            'amount' => $this->faker->randomFloat(2, 20, 200),
            'payment_date' => $this->faker->dateTimeThisYear(),
            'status' => $this->faker->randomElement(['paid', 'pending', 'overdue']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

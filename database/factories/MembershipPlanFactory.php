<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    protected $model = MembershipPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'monthly_fee' => $this->faker->randomFloat(2, 30, 150),
            'weekly_frequency' => $this->faker->numberBetween(1, 5),
            'start_date' => $this->faker->dateTimeThisYear(),
            'is_active' => true,
        ];
    }
}

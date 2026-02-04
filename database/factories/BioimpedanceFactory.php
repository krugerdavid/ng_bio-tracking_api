<?php

namespace Database\Factories;

use App\Models\Bioimpedance;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bioimpedance>
 */
class BioimpedanceFactory extends Factory
{
    protected $model = Bioimpedance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weight = $this->faker->randomFloat(2, 50, 120);
        $height = $this->faker->randomFloat(2, 1.5, 2.0);
        $imc = round($weight / ($height * $height), 2);

        return [
            'member_id' => Member::factory(),
            'date' => $this->faker->dateTimeThisYear(),
            'height' => $height,
            'weight' => $weight,
            'imc' => $imc,
            'body_fat_percentage' => $this->faker->randomFloat(2, 10, 40),
            'muscle_mass_percentage' => $this->faker->randomFloat(2, 30, 50),
            'kcal' => $this->faker->randomFloat(2, 1500, 2500),
            'metabolic_age' => $this->faker->numberBetween(25, 60),
            'visceral_fat_percentage' => $this->faker->randomFloat(2, 1, 20),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

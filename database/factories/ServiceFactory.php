<?php

namespace Database\Factories;

use App\Enums\ServiceTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Haircut', 'Beard Trim', 'Shampoo', 'Full Service']),
            'type' => fake()->randomElement(ServiceTypeEnum::cases()),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'price' => fake()->randomFloat(2, 10, 100),
        ];
    }
}

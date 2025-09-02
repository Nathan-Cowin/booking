<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\BarberService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarberService>
 */
class BarberServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barber_id' => Barber::factory(),
            'service_id' => Service::factory(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'duration_minutes' => $this->faker->randomElement([15, 30, 45, 60, 90]),
        ];
    }
}

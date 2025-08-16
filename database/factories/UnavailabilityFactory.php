<?php

namespace Database\Factories;

use App\Models\Barber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unavailability>
 */
class UnavailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+7 days');
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'barber_id' => Barber::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => fake()->word(),
        ];
    }
}

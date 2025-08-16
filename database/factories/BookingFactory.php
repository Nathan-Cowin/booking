<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+7 days');
        $endTime = (clone $startTime)->modify('+30 minutes');

        return [
            'barber_id' => Barber::factory(),
            'service_id' => Service::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'status' => fake()->randomElement(['confirmed', 'pending', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

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
        ];
    }
}

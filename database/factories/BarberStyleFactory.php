<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\BarberStyle;
use App\Models\Style;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarberStyle>
 */
class BarberStyleFactory extends Factory
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
            'style_id' => Style::factory(),
        ];
    }
}

<?php

use App\Models\Barber;

it('gets all barbers', function () {
    Barber::factory()->count(3)->create();

    $this->get('/api/barbers')
        ->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'id',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ])
        ->assertJsonCount(3);
});

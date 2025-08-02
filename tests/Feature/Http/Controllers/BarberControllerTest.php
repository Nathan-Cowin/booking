<?php

use App\Models\Barber;

it('gets all barbers', function () {
    Barber::factory()->count(5)->create();

    $response = $this->get('/api/barbers');

    $response->assertStatus(200)->assertJsonCount(5);
});

<?php

use App\Models\Barber;
use App\Models\Service;
use App\Models\User;

use function Pest\Laravel\getJson;

it('can get all services for a barber', function () {
    $user = User::factory()->create();
    $barber = Barber::factory()->for($user)->create();

    $services = Service::factory()->count(3)->create();
    $barber->services()->attach($services);

    $response = getJson("/api/barbers/{$barber->id}/services");

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'created_at',
                'updated_at',
            ],
        ]);
});

it('returns empty array when barber has no services', function () {
    $user = User::factory()->create();
    $barber = Barber::factory()->for($user)->create();

    $response = getJson("/api/barbers/{$barber->id}/services");

    $response->assertStatus(200)
        ->assertJsonCount(0);
});

it('returns 404 when barber does not exist', function () {
    $response = getJson('/api/barbers/999/services');

    $response->assertStatus(404);
});

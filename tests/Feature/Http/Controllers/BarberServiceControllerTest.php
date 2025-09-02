<?php

use App\Models\Barber;
use App\Models\Service;
use App\Models\User;

use function Pest\Laravel\getJson;

it('can get all services for a barber', function () {
    $user = User::factory()->create();
    $barber = Barber::factory()->for($user)->create();

    $services = Service::factory()->count(3)->create();
    
    foreach ($services as $index => $service) {
        $barber->services()->attach($service, [
            'price' => 2000 + ($index * 500),
            'duration_minutes' => 30 + ($index * 15),
        ]);
    }

    $response = getJson("/api/barbers/{$barber->id}/services");

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'type',
                'description',
                'duration_minutes',
                'price',
                'created_at',
                'updated_at',
            ],
        ]);

    $responseData = $response->json();
    expect($responseData[0]['price'])->toBe(2000);
    expect($responseData[0]['duration_minutes'])->toBe(30);
    expect($responseData[1]['price'])->toBe(2500);
    expect($responseData[1]['duration_minutes'])->toBe(45);
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

<?php

use App\Models\Barber;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('can check service compatibility', function () {
    // Create a test barber and services
    $user = User::factory()->create(['name' => 'Test Barber']);
    $barber = Barber::factory()->create(['user_id' => $user->id]);
    
    $service1 = Service::factory()->create(['name' => 'Haircut', 'type' => 'hair']);
    $service2 = Service::factory()->create(['name' => 'Beard Trim', 'type' => 'beard']);
    $service3 = Service::factory()->create(['name' => 'Shampoo', 'type' => 'hair']);
    
    // Associate services with barber
    $barber->services()->attach($service1->id, ['price' => 2000, 'duration_minutes' => 30]);
    $barber->services()->attach($service2->id, ['price' => 1500, 'duration_minutes' => 15]);
    
    // Test compatible services
    $response = $this->postJson('/api/services/compatibility', [
        'service_ids' => [$service1->id, $service2->id]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'compatible' => true,
        'compatible_barber_count' => 1,
    ]);
    
    // Test incompatible services (service3 is not associated with barber)
    $response = $this->postJson('/api/services/compatibility', [
        'service_ids' => [$service1->id, $service3->id]
    ]);
    
    $response->assertStatus(200);
    $response->assertJson([
        'compatible' => false,
        'compatible_barber_count' => 0,
        'message' => 'No barbers offer this combination of services'
    ]);
});

it('can get multi-barber availability', function () {
    // Create test data
    $user1 = User::factory()->create(['name' => 'Barber One']);
    $barber1 = Barber::factory()->create(['user_id' => $user1->id]);
    
    $user2 = User::factory()->create(['name' => 'Barber Two']);
    $barber2 = Barber::factory()->create(['user_id' => $user2->id]);
    
    $service1 = Service::factory()->create(['name' => 'Haircut', 'type' => 'hair']);
    
    // Both barbers offer the same service
    $barber1->services()->attach($service1->id, ['price' => 2000, 'duration_minutes' => 30]);
    $barber2->services()->attach($service1->id, ['price' => 2000, 'duration_minutes' => 30]);
    
    $response = $this->getJson('/api/availability?' . http_build_query([
        'date' => now()->addDay()->format('Y-m-d'),
        'service_ids' => [$service1->id]
    ]));
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'date',
        'compatible_barbers',
        'available_slots'
    ]);
    
    $data = $response->json();
    expect($data['compatible_barbers'])->toHaveCount(2);
});
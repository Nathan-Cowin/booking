<?php

use App\Models\Barber;
use App\Models\Bookings;
use App\Models\Service;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use Carbon\Carbon;

beforeEach(function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $this->barber = Barber::factory()->create(['user_id' => $user->id]);
    $this->service = Service::factory()->create([
        'name' => 'Haircut',
        'duration_minutes' => 30,
        'price' => 25.00,
    ]);
    $this->barber->services()->attach($this->service->id);

    WorkingHours::factory()->create([
        'barber_id' => $this->barber->id,
        'day_of_week' => Carbon::today()->format('l'),
        'start_time' => '09:00',
        'end_time' => '18:00',
        'is_available' => true,
    ]);
});

it('can create a booking successfully', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '1234567890',
        'notes' => 'First time customer',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'booking' => [
                'id',
                'barber_name',
                'service_name',
                'start_time',
                'end_time',
                'customer_name',
                'customer_email',
                'customer_phone',
                'status',
                'notes',
            ],
        ]);

    expect($response->json('booking.barber_name'))->toBe('John Doe');
    expect($response->json('booking.service_name'))->toBe('Haircut');
    expect($response->json('booking.customer_name'))->toBe('Jane Smith');
    expect($response->json('booking.status'))->toBe('confirmed');

    $this->assertDatabaseHas('bookings', [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'customer_email' => 'jane@example.com',
        'status' => 'confirmed',
    ]);

    Carbon::setTestNow();
});

it('validates required fields', function () {
    $response = $this->postJson('/api/bookings', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'barber_id',
            'service_id',
            'start_time',
            'customer_name',
            'customer_email',
        ]);
});

it('validates email format', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'customer_name' => 'Jane Smith',
        'customer_email' => 'invalid-email',
        'customer_phone' => '1234567890',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['customer_email']);
});

it('validates datetime format for start_time', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => 'invalid-date',
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_time']);
});

it('rejects booking for non-existent barber', function () {
    $bookingData = [
        'barber_id' => 999,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barber_id']);
});

it('rejects booking for non-existent service', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => 999,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_id']);
});

it('rejects booking when barber has no working hours for the day', function () {
    // Delete the working hours created in beforeEach
    $this->barber->workingHours()->delete();
    
    // Create working hours for tomorrow instead
    WorkingHours::factory()->create([
        'barber_id' => $this->barber->id,
        'day_of_week' => Carbon::tomorrow()->format('l'),
        'start_time' => '09:00',
        'end_time' => '18:00',
        'is_available' => true,
    ]);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The selected time slot is not available',
        ]);
});

it('rejects booking outside working hours', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(20, 0)->format('Y-m-d H:i'), // 8 PM, outside working hours
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The selected time slot is not available',
            'errors' => [
                'start_time' => ['The selected time slot conflicts with existing bookings or unavailable hours'],
            ],
        ]);
});

it('rejects booking for past time', function () {
    // Set current time to ensure we're testing past time check
    Carbon::setTestNow(Carbon::today()->setTime(12, 0));
    
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(11, 0)->format('Y-m-d H:i'), // 1 hour ago
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The selected time slot is not available',
        ]);
        
    Carbon::setTestNow();
});

it('rejects booking that conflicts with existing booking', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    // Create existing booking
    Bookings::factory()->create([
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0),
        'end_time' => Carbon::today()->setTime(10, 30),
        'status' => 'confirmed',
    ]);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 15)->format('Y-m-d H:i'), // Overlaps existing booking
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The selected time slot is not available',
        ]);

    Carbon::setTestNow();
});

it('rejects booking that conflicts with unavailability', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    // Create unavailability
    Unavailability::factory()->create([
        'barber_id' => $this->barber->id,
        'start_time' => Carbon::today()->setTime(12, 0),
        'end_time' => Carbon::today()->setTime(13, 0),
    ]);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(12, 30)->format('Y-m-d H:i'), // During lunch break
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'The selected time slot is not available',
        ]);

    Carbon::setTestNow();
});

it('ignores cancelled bookings when checking availability', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    // Create cancelled booking
    Bookings::factory()->create([
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0),
        'end_time' => Carbon::today()->setTime(10, 30),
        'status' => 'cancelled',
    ]);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'), // Same time as cancelled booking
        'customer_name' => 'Jane Smith',
        'customer_email' => 'jane@example.com',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(201);

    Carbon::setTestNow();
});

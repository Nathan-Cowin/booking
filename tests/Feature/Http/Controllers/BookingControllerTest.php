<?php

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use Carbon\Carbon;

beforeEach(function () {
    // Create and set current tenant
    $this->tenant = Tenant::create([
        'name' => 'test_salon_'.uniqid(),
    ]);
    $this->tenant->makeCurrent();

    // Create barber user and barber
    $barberUser = User::factory()->create(['name' => 'John Doe']);
    $this->barber = Barber::factory()->create(['user_id' => $barberUser->id]);

    // Create services
    $this->service1 = Service::factory()->create([
        'name' => 'Haircut',
        'duration_minutes' => 30,
        'price' => 25.00,
    ]);
    $this->service2 = Service::factory()->create([
        'name' => 'Beard Trim',
        'duration_minutes' => 15,
        'price' => 15.00,
    ]);
    $this->barber->services()->attach([$this->service1->id, $this->service2->id]);

    // Create client user and client
    $clientUser = User::factory()->create(['email' => 'client@example.com']);
    $this->client = Client::factory()->create(['user_id' => $clientUser->id]);

    // Set up authentication
    $this->actingAs($clientUser, 'sanctum');

    WorkingHours::factory()->create([
        'barber_id' => $this->barber->id,
        'day_of_week' => Carbon::today()->format('l'),
        'start_time' => '09:00',
        'end_time' => '18:00',
        'is_available' => true,
    ]);
});

it('can create a booking with single service successfully', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'notes' => 'First time customer',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'booking' => [
                'id',
                'barber',
                'services',
                'start_time',
                'end_time',
                'status',
                'notes',
            ],
        ]);

    expect($response->json('booking.status'))->toBe('confirmed');

    $this->assertDatabaseHas('bookings', [
        'barber_id' => $this->barber->id,
        'client_id' => $this->client->id,
        'status' => 'confirmed',
    ]);

    $this->assertDatabaseHas('booking_service', [
        'service_id' => $this->service1->id,
    ]);

    Carbon::setTestNow();
});

it('can create a booking with multiple services successfully', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [$this->service1->id, $this->service2->id],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
        'notes' => 'Haircut and beard trim',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('bookings', [
        'barber_id' => $this->barber->id,
        'client_id' => $this->client->id,
        'status' => 'confirmed',
    ]);

    $booking = $response->json('booking');
    $bookingId = $booking['id'];

    $this->assertDatabaseHas('booking_service', [
        'booking_id' => $bookingId,
        'service_id' => $this->service1->id,
    ]);

    $this->assertDatabaseHas('booking_service', [
        'booking_id' => $bookingId,
        'service_id' => $this->service2->id,
    ]);

    Carbon::setTestNow();
});

it('validates required fields', function () {
    $response = $this->postJson('/api/bookings', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'barber_id',
            'service_ids',
            'start_time',
        ]);
});

it('validates service_ids array format', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => 'not-an-array',
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids']);
});

it('validates empty service_ids array', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids']);
});

it('validates datetime format for start_time', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [$this->service1->id],
        'start_time' => 'invalid-date',
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_time']);
});

it('rejects booking for non-existent barber', function () {
    $bookingData = [
        'barber_id' => 999,
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barber_id']);
});

it('rejects booking for non-existent service', function () {
    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [999],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids.0']);
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
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'),
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
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(20, 0)->format('Y-m-d H:i'), // 8 PM, outside working hours
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
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(11, 0)->format('Y-m-d H:i'), // 1 hour ago
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
    $existingBooking = Booking::factory()->create([
        'barber_id' => $this->barber->id,
        'client_id' => $this->client->id,
        'start_time' => Carbon::today()->setTime(10, 0),
        'end_time' => Carbon::today()->setTime(10, 30),
        'status' => 'confirmed',
    ]);
    $existingBooking->services()->attach($this->service1->id);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(10, 15)->format('Y-m-d H:i'), // Overlaps existing booking
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
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(12, 30)->format('Y-m-d H:i'), // During lunch break
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
    $cancelledBooking = Booking::factory()->create([
        'barber_id' => $this->barber->id,
        'client_id' => $this->client->id,
        'start_time' => Carbon::today()->setTime(10, 0),
        'end_time' => Carbon::today()->setTime(10, 30),
        'status' => 'cancelled',
    ]);
    $cancelledBooking->services()->attach($this->service1->id);

    $bookingData = [
        'barber_id' => $this->barber->id,
        'service_ids' => [$this->service1->id],
        'start_time' => Carbon::today()->setTime(10, 0)->format('Y-m-d H:i'), // Same time as cancelled booking
    ];

    $response = $this->postJson('/api/bookings', $bookingData);

    $response->assertStatus(201);

    Carbon::setTestNow();
});

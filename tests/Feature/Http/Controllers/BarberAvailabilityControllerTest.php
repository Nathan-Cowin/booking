<?php

use App\Models\Barber;
use App\Models\Booking;
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

it('returns available slots for a given date', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    $date = Carbon::today()->format('Y-m-d');

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$this->service->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'date',
            'barber_id',
            'barber_name',
            'total_duration_minutes',
            'available_slots' => [
                '*' => [
                    'start_time',
                    'end_time',
                    'duration_minutes',
                ],
            ],
        ]);

    expect($response->json('date'))->toBe($date)
        ->and($response->json('barber_id'))->toBe($this->barber->id)
        ->and($response->json('barber_name'))->toBe('John Doe')
        ->and($response->json('total_duration_minutes'))->toBe(30)
        ->and(count($response->json('available_slots')))->toBeGreaterThan(0);

    Carbon::setTestNow();
});

it('calculates correct duration for multiple services', function () {
    $service2 = Service::factory()->create([
        'name' => 'Beard Trim',
        'duration_minutes' => 15,
    ]);
    $this->barber->services()->attach($service2->id);

    $date = Carbon::today()->format('Y-m-d');
    $serviceIds = [$this->service->id, $service2->id];

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$serviceIds[0]}&service_ids[]={$serviceIds[1]}");

    $response->assertStatus(200);
    expect($response->json('total_duration_minutes'))->toBe(45);
});

it('excludes slots blocked by existing bookings', function () {
    $date = Carbon::today();

    Booking::factory()->create([
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => $date->copy()->setTime(10, 0),
        'end_time' => $date->copy()->setTime(10, 30),
        'customer_name' => 'Jane Smith',
        'status' => 'confirmed',
    ]);

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date->format('Y-m-d')}&service_ids[]={$this->service->id}");

    $response->assertStatus(200);

    $slots = collect($response->json('available_slots'));
    $blockedSlot = $slots->first(fn ($slot) => $slot['start_time'] === '10:00');

    expect($blockedSlot)->toBeNull();
});

it('excludes slots blocked by unavailabilities', function () {
    $date = Carbon::today();

    Unavailability::factory()->create([
        'barber_id' => $this->barber->id,
        'start_time' => $date->copy()->setTime(12, 0),
        'end_time' => $date->copy()->setTime(13, 0),
        'reason' => 'Lunch break',
    ]);

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date->format('Y-m-d')}&service_ids[]={$this->service->id}");

    $response->assertStatus(200);

    $slots = collect($response->json('available_slots'));
    $blockedSlots = $slots->filter(fn ($slot) => $slot['start_time'] >= '12:00' && $slot['start_time'] < '13:00'
    );

    expect($blockedSlots->count())->toBe(0);
});

it('ignores cancelled bookings', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    $date = Carbon::today();

    Booking::factory()->create([
        'barber_id' => $this->barber->id,
        'service_id' => $this->service->id,
        'start_time' => $date->copy()->setTime(14, 0),
        'end_time' => $date->copy()->setTime(14, 30),
        'customer_name' => 'Cancelled Customer',
        'status' => 'cancelled',
    ]);

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date->format('Y-m-d')}&service_ids[]={$this->service->id}");

    $response->assertStatus(200);

    $slots = collect($response->json('available_slots'));
    $availableSlot = $slots->first(fn ($slot) => $slot['start_time'] === '14:00');

    expect($availableSlot)->not->toBeNull();

    Carbon::setTestNow();
});

it('validates required date parameter', function () {
    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

it('validates service_ids exist', function () {
    $date = Carbon::today()->format('Y-m-d');
    $invalidServiceId = 999;

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$invalidServiceId}");

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids.0']);
});

it('returns slots within working hours only', function () {
    $date = Carbon::today()->format('Y-m-d');

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$this->service->id}");

    $response->assertStatus(200);

    $slots = collect($response->json('available_slots'));

    foreach ($slots as $slot) {
        expect($slot['start_time'])->toBeGreaterThanOrEqual('09:00');
        expect($slot['end_time'])->toBeLessThanOrEqual('18:00');
    }
});

it('handles longer service durations correctly', function () {
    $longService = Service::factory()->create([
        'name' => 'Full Service',
        'duration_minutes' => 120,
    ]);
    $this->barber->services()->attach($longService->id);

    $date = Carbon::today()->format('Y-m-d');

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$longService->id}");

    $response->assertStatus(200);
    expect($response->json('total_duration_minutes'))->toBe(120);

    $slots = collect($response->json('available_slots'));

    foreach ($slots as $slot) {
        expect($slot['duration_minutes'])->toBe(120);
        expect($slot['end_time'])->toBeLessThanOrEqual('18:00');
    }
});

it('returns empty slots when barber has no working hours for the day', function () {
    $tomorrow = Carbon::tomorrow();

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$tomorrow->format('Y-m-d')}&service_ids[]={$this->service->id}");

    $response->assertStatus(200)
        ->assertJson([
            'date' => $tomorrow->format('Y-m-d'),
            'barber_id' => $this->barber->id,
            'available_slots' => [],
        ]);
});

it('returns empty slots when barber is not available for the day', function () {
    WorkingHours::where('barber_id', $this->barber->id)
        ->where('day_of_week', Carbon::today()->format('l'))
        ->update(['is_available' => false]);

    $date = Carbon::today()->format('Y-m-d');

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$this->service->id}");

    $response->assertStatus(200)
        ->assertJson([
            'available_slots' => [],
        ]);
});

it('only shows availability after current time for todays date', function () {
    Carbon::setTestNow(Carbon::today()->setTime(10, 15));

    $date = Carbon::today()->format('Y-m-d');

    $response = $this->getJson("/api/barbers/{$this->barber->id}/availability?date={$date}&service_ids[]={$this->service->id}");

    $response->assertStatus(200);

    $slots = collect($response->json('available_slots'));

    foreach ($slots as $slot) {
        expect($slot['start_time'])->toBeGreaterThan('10:15');
    }

    expect($slots->first()['start_time'])->toBe('10:30');

    Carbon::setTestNow();
});

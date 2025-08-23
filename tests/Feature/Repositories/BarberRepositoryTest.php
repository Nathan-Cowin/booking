<?php

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use App\Repositories\BarberRepository;
use Carbon\Carbon;

describe('workingHours', function () {
    it('finds working hours for available day', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15'); // Monday

        WorkingHours::factory()->create([
            'barber_id' => $barber->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_available' => true,
        ]);

        $result = new BarberRepository()->workingHours($barber, $date);

        expect($result)->toBeInstanceOf(WorkingHours::class)
            ->and($result->day_of_week)->toBe('Monday')
            ->and($result->is_available)->toBeTrue();
    });

    it('returns null when no working hours exist for day', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        $result = new BarberRepository()->workingHours($barber, $date);

        expect($result)->toBeNull();
    });

    it('returns null when barber is not available on day', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        WorkingHours::factory()->create([
            'barber_id' => $barber->id,
            'day_of_week' => 'Monday',
            'is_available' => false,
        ]);

        $result = new BarberRepository()->workingHours($barber, $date);

        expect($result)->toBeNull();
    });

    it('only returns working hours for correct barber', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        $otherBarber = Barber::factory()->for(User::factory())->create();

        WorkingHours::factory()->create([
            'barber_id' => $otherBarber->id,
            'day_of_week' => 'Monday',
            'is_available' => true,
        ]);

        $result = new BarberRepository()->workingHours($barber, $date);

        expect($result)->toBeNull();
    });
});

describe('unavailabilities', function () {
    it('gets unavailabilities for specific date', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        Unavailability::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
        ]);

        Unavailability::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(14, 0),
            'end_time' => $date->copy()->setTime(15, 0),
        ]);

        $result = new BarberRepository()->unavailabilities($barber, $date);

        expect($result)->toHaveCount(2)
            ->and($result->first())->toHaveKeys(['start_time', 'end_time']);
    });

    it('excludes unavailabilities from different dates', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        Unavailability::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
        ]);

        Unavailability::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->addDay()->setTime(10, 0),
            'end_time' => $date->copy()->addDay()->setTime(11, 0),
        ]);

        $result = new BarberRepository()->unavailabilities($barber, $date);

        expect($result)->toHaveCount(1);
    });

    it('excludes unavailabilities from different barbers', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        $otherBarber = Barber::factory()->for(User::factory())->create();

        Unavailability::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
        ]);

        Unavailability::factory()->create([
            'barber_id' => $otherBarber->id,
            'start_time' => $date->copy()->setTime(14, 0),
            'end_time' => $date->copy()->setTime(15, 0),
        ]);

        $result = new BarberRepository()->unavailabilities($barber, $date);

        expect($result)->toHaveCount(1);
    });

    it('returns empty collection when no unavailabilities exist', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        $result = new BarberRepository()->unavailabilities($barber, $date);

        expect($result)->toHaveCount(0);
    });
});

describe('existingBookings', function () {
    it('gets bookings for specific date excluding cancelled', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(14, 0),
            'end_time' => $date->copy()->setTime(15, 0),
            'status' => 'completed',
        ]);

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(16, 0),
            'end_time' => $date->copy()->setTime(17, 0),
            'status' => 'cancelled',
        ]);

        $result = new BarberRepository()->existingBookings($barber, $date);

        expect($result)->toHaveCount(2)
            ->and($result->pluck('status')->toArray())->not->toContain('cancelled');
    });

    it('excludes bookings from different dates', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->addDay()->setTime(10, 0),
            'end_time' => $date->copy()->addDay()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $result = new BarberRepository()->existingBookings($barber, $date);

        expect($result)->toHaveCount(1);
    });

    it('excludes bookings from different barbers', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        $otherBarber = Barber::factory()->for(User::factory())->create();

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        Booking::factory()->create([
            'barber_id' => $otherBarber->id,
            'start_time' => $date->copy()->setTime(14, 0),
            'end_time' => $date->copy()->setTime(15, 0),
            'status' => 'confirmed',
        ]);

        $result = new BarberRepository()->existingBookings($barber, $date);

        expect($result)->toHaveCount(1);
    });

    it('returns only start_time and end_time fields', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $date = Carbon::parse('2024-01-15');

        Booking::factory()->create([
            'barber_id' => $barber->id,
            'start_time' => $date->copy()->setTime(10, 0),
            'end_time' => $date->copy()->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        $result = new BarberRepository()->existingBookings($barber, $date);

        $booking = $result->first();
        expect($booking)->toHaveKeys(['start_time', 'end_time']);
    });
});

describe('serviceDuration', function () {
    it('calculates total duration for single service', function () {
        $barber = Barber::factory()->for(User::factory())
            ->has(Service::factory(['duration_minutes' => 30]))
            ->create();

        $result = new BarberRepository()->serviceDuration($barber, [$barber->services->first()->id]);

        expect($result)->toBe(30);
    });

    it('calculates total duration for multiple services', function () {
        $barber = Barber::factory()->for(User::factory())
            ->has(Service::factory(['duration_minutes' => 30]))
            ->has(Service::factory(['duration_minutes' => 45]))
            ->has(Service::factory(['duration_minutes' => 15]))
            ->create();

        $serviceIds = $barber->services->pluck('id')->toArray();
        $result = new BarberRepository()->serviceDuration($barber, $serviceIds);

        expect($result)->toBe(90);
    });

    it('only includes services associated with barber', function () {
        $barber = Barber::factory()->for(User::factory())
            ->has(Service::factory(['duration_minutes' => 30]))
            ->create();
        $otherService = Service::factory()->create(['duration_minutes' => 60]);

        $result = new BarberRepository()->serviceDuration(
            $barber,
            [$barber->services->first()->id, $otherService->id]
        );

        expect($result)->toBe(30); // Only the barber's service
    });

    it('returns zero when no services match', function () {
        $barber = Barber::factory()->for(User::factory())->create();

        $service = Service::factory()->create(['duration_minutes' => 30]);
        // Don't attach service to barber

        $result = new BarberRepository()->serviceDuration($barber, [$service->id]);

        expect($result)->toBe(0);
    });

    it('returns zero for empty service ids array', function () {
        $barber = Barber::factory()->for(User::factory())->create();

        $result = new BarberRepository()->serviceDuration($barber, []);

        expect($result)->toBe(0);
    });

    it('handles partial matches correctly', function () {
        $barber = Barber::factory()->for(User::factory())
            ->has(Service::factory(['duration_minutes' => 30]))
            ->has(Service::factory(['duration_minutes' => 45]))
            ->create();
        $service3 = Service::factory()->create(['duration_minutes' => 60]);

        $serviceIds = $barber->services->pluck('id')->toArray();
        $serviceIds[] = $service3->id;
        $result = new BarberRepository()->serviceDuration($barber, $serviceIds);

        expect($result)->toBe(75); // 30 + 45, excludes service3
    });
});

<?php

use App\Contracts\BarberRepositoryInterface;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use App\Services\BarberAvailabilityService;

use function Pest\Laravel\travelTo;

describe('availableSlots', function () {
    it('returns available slots for future dates without adjusting start time', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $service = Service::factory(['duration_minutes' => 30])->create();
        $barber->services()->attach($service->id, ['price' => 2000, 'duration_minutes' => 30]);
        $futureDate = now()->tomorrow();

        WorkingHours::factory()->for($barber)->create([
            'day_of_week' => $futureDate->format('l'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_available' => true,
        ]);

        $slots = new BarberAvailabilityService(app(BarberRepositoryInterface::class))->availableSlots($barber, $futureDate, [$service->id]);

        expect($slots)->not->toBeEmpty()
            ->and($slots[0]['start_time'])->toBe('09:00');
    });

    it('handles unavailability overlap correctly', function () {
        travelTo(now()->hour(8));

        $barber = Barber::factory()->for(User::factory())->create();
        $service = Service::factory(['duration_minutes' => 30])->create();
        $barber->services()->attach($service->id, ['price' => 2000, 'duration_minutes' => 30]);

        $date = today();

        WorkingHours::factory()->for($barber)->create([
            'day_of_week' => $date->format('l'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_available' => true,
        ]);

        Unavailability::factory()->for($barber)->create([
            'start_time' => $date->copy()->setTime(9, 45), // This will overlap with 9:30-10:00 slot
            'end_time' => $date->copy()->setTime(10, 15),
        ]);

        $slots = new BarberAvailabilityService(app(BarberRepositoryInterface::class))->availableSlots($barber, $date, [$service->id]);

        $nineThirtySlot = array_filter($slots, fn ($slot) => $slot['start_time'] === '09:30');
        expect($nineThirtySlot)->toBeEmpty();
    })->freezeTime();

    it('handles booking overlap correctly', function () {
        travelTo(now()->hour(8));

        $barber = Barber::factory()->for(User::factory())->create();
        $service = Service::factory(['duration_minutes' => 30])->create();
        $barber->services()->attach($service->id, ['price' => 2000, 'duration_minutes' => 30]);

        WorkingHours::factory()->for($barber)->create([
            'day_of_week' => today()->format('l'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_available' => true,
        ]);

        $booking = Booking::factory()->for($barber)->create([
            'start_time' => today()->copy()->setTime(10, 15),
            'end_time' => today()->copy()->setTime(10, 45),
            'status' => 'confirmed',
        ]);
        $booking->services()->attach($service->id);

        $slots = new BarberAvailabilityService(app(BarberRepositoryInterface::class))->availableSlots($barber, today(), [$service->id]);

        $tenOClockSlot = array_filter($slots, fn ($slot) => $slot['start_time'] === '10:00');
        expect($tenOClockSlot)->toBeEmpty();
    });
})->freezeTime();

describe('serviceDuration', function () {
    it('calculateTotalServiceDuration delegates to repository', function () {
        $barber = Barber::factory()->for(User::factory())->create();
        $service = Service::factory(['duration_minutes' => 30])->create();
        $barber->services()->attach($service->id, ['price' => 2000, 'duration_minutes' => 30]);

        $duration = new BarberAvailabilityService(app(BarberRepositoryInterface::class))->serviceDuration($barber, [$service->id]);

        expect($duration)->toBe(30);
    });
});

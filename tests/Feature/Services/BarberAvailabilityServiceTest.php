<?php

use App\Models\Barber;
use App\Models\Bookings;
use App\Models\Service;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\WorkingHours;
use App\Services\BarberAvailabilityService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->barber = Barber::factory()->create(['user_id' => $this->user->id]);
    $this->testService = Service::factory()->create(['duration_minutes' => 30]);
    $this->barber->services()->attach($this->testService->id);
    
    $this->availabilityService = new BarberAvailabilityService(app(\App\Contracts\BarberRepositoryInterface::class));
});

describe('BarberAvailabilityService', function () {
    it('returns available slots for future dates without adjusting start time', function () {
        $futureDate = Carbon::tomorrow();
        
        WorkingHours::factory()->create([
            'barber_id' => $this->barber->id,
            'day_of_week' => $futureDate->format('l'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_available' => true,
        ]);
        
        $slots = $this->availabilityService->availableSlots($this->barber, $futureDate, [$this->testService->id]);
        
        expect($slots)->not->toBeEmpty();
        expect($slots[0]['start_time'])->toBe('09:00');
    });

    it('handles unavailability overlap correctly', function () {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0)); // Set early time
        
        $date = Carbon::today();
        
        WorkingHours::factory()->create([
            'barber_id' => $this->barber->id,
            'day_of_week' => $date->format('l'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_available' => true,
        ]);
        
        // Create unavailability that will cause overlap with 9:30 slot
        Unavailability::factory()->create([
            'barber_id' => $this->barber->id,
            'start_time' => $date->copy()->setTime(9, 45), // This will overlap with 9:30-10:00 slot
            'end_time' => $date->copy()->setTime(10, 15),
        ]);
        
        $slots = $this->availabilityService->availableSlots($this->barber, $date, [$this->testService->id]);
        
        // Should not include the 9:30 slot that overlaps
        $nineThirtySlot = array_filter($slots, fn($slot) => $slot['start_time'] === '09:30');
        expect($nineThirtySlot)->toBeEmpty();
        
        Carbon::setTestNow();
    });

    it('handles booking overlap correctly', function () {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0)); // Set early time
        
        $date = Carbon::today();
        
        WorkingHours::factory()->create([
            'barber_id' => $this->barber->id,
            'day_of_week' => $date->format('l'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_available' => true,
        ]);
        
        // Create booking that will overlap with 10:00 slot
        Bookings::factory()->create([
            'barber_id' => $this->barber->id,
            'service_id' => $this->testService->id,
            'start_time' => $date->copy()->setTime(10, 15), // This will overlap with 10:00-10:30 slot
            'end_time' => $date->copy()->setTime(10, 45),
            'status' => 'confirmed',
        ]);
        
        $slots = $this->availabilityService->availableSlots($this->barber, $date, [$this->testService->id]);
        
        // Should not include the 10:00 slot that overlaps
        $tenOClockSlot = array_filter($slots, fn($slot) => $slot['start_time'] === '10:00');
        expect($tenOClockSlot)->toBeEmpty();
        
        Carbon::setTestNow();
    });

    it('calculateTotalServiceDuration delegates to repository', function () {
        $duration = $this->availabilityService->calculateTotalServiceDuration($this->barber, [$this->testService->id]);
        
        expect($duration)->toBe(30);
    });
});
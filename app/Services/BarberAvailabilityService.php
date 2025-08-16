<?php

namespace App\Services;

use App\Contracts\BarberRepositoryInterface;
use App\Models\Barber;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BarberAvailabilityService
{
    private const SLOT_INCREMENT_MINUTES = 15;

    public function __construct(
        private readonly BarberRepositoryInterface $barberRepository
    ) {}

    public function availableSlots(Barber $barber, Carbon $date, array $serviceIds): array
    {
        $workingHours = $this->barberRepository->workingHours($barber, $date);

        if (! $workingHours) {
            return [];
        }

        $totalDuration = $this->barberRepository->calculateTotalServiceDuration($barber, $serviceIds);
        $unavailabilities = $this->barberRepository->unavailabilities($barber, $date);
        $existingBookings = $this->barberRepository->existingBookings($barber, $date);

        return $this->generateAvailableSlots(
            $date,
            $workingHours,
            $totalDuration,
            $unavailabilities,
            $existingBookings
        );
    }

    public function calculateTotalServiceDuration(Barber $barber, array $serviceIds): int
    {
        return $this->barberRepository->calculateTotalServiceDuration($barber, $serviceIds);
    }

    private function generateAvailableSlots(
        Carbon $date,
        WorkingHours $workingHours,
        int $serviceDuration,
        Collection $unavailabilities,
        Collection $bookings
    ): array {
        $slots = [];

        $startTime = $date->copy()->setTimeFromTimeString($workingHours->start_time->format('H:i'));
        $endTime = $date->copy()->setTimeFromTimeString($workingHours->end_time->format('H:i'));
        $current = $this->adjustStartTimeForToday($date, $startTime);

        while ($current->copy()->addMinutes($serviceDuration)->lte($endTime)) {
            $slotEnd = $current->copy()->addMinutes($serviceDuration);

            if ($this->isSlotAvailable($current, $slotEnd, $unavailabilities, $bookings)) {
                $slots[] = [
                    'start_time' => $current->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $serviceDuration,
                ];
            }

            $current->addMinutes(self::SLOT_INCREMENT_MINUTES);
        }

        return $slots;
    }

    private function adjustStartTimeForToday(Carbon $date, Carbon $startTime): Carbon
    {
        if (! $date->isToday()) {
            return $startTime;
        }

        $now = Carbon::now();

        return $now->greaterThan($startTime)
            ? $now->addMinutes(self::SLOT_INCREMENT_MINUTES)
            : $startTime;
    }

    private function isSlotAvailable(
        Carbon $slotStart,
        Carbon $slotEnd,
        Collection $unavailabilities,
        Collection $bookings
    ): bool {
        foreach ($unavailabilities as $unavailability) {
            if ($this->timesOverlap($slotStart, $slotEnd, $unavailability->start_time, $unavailability->end_time)) {
                return false;
            }
        }

        foreach ($bookings as $booking) {
            if ($this->timesOverlap($slotStart, $slotEnd, $booking->start_time, $booking->end_time)) {
                return false;
            }
        }

        return true;
    }

    private function timesOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool
    {
        return $start1->lt($end2) && $end1->gt($start2);
    }
}

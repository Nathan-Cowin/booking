<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarberAvailabilityRequest;
use App\Models\Barber;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class BarberAvailabilityController extends Controller
{
    public function index(BarberAvailabilityRequest $request, Barber $barber): JsonResponse
    {
        $date = Carbon::parse($request->date)->startOfDay();
        $dayOfWeek = $date->format('l');

        $totalDuration = (int) $barber->services()
            ->whereIn('services.id', $request->service_ids)
            ->sum('duration_minutes');

        $workingHours = $barber->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $workingHours) {
            return response()->json([
                'date' => $date->format('Y-m-d'),
                'barber_id' => $barber->id,
                'barber_name' => $barber->user->name,
                'total_duration_minutes' => $totalDuration,
                'available_slots' => [],
                'message' => 'Barber is not available on this day',
            ]);
        }

        $unavailabilities = $barber->unavailabilities()
            ->whereDate('start_time', $date)
            ->get(['start_time', 'end_time']);

        $existingBookings = $barber->bookings()
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->get(['start_time', 'end_time']);

        $slots = $this->generateAvailableSlots(
            $date,
            $workingHours,
            $totalDuration,
            $unavailabilities,
            $existingBookings
        );

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'barber_id' => $barber->id,
            'barber_name' => $barber->user->name,
            'total_duration_minutes' => $totalDuration,
            'available_slots' => $slots,
        ]);
    }

    private function generateAvailableSlots(Carbon $date, WorkingHours $workingHours, int $serviceDuration, $unavailabilities, $bookings): array
    {
        $slots = [];
        $slotDuration = $workingHours->slot_duration_minutes;

        $startTime = $date->copy()->setTimeFromTimeString($workingHours->start_time->format('H:i'));
        $endTime = $date->copy()->setTimeFromTimeString($workingHours->end_time->format('H:i'));

        $current = $startTime->copy();

        if ($date->isToday()) {
            $now = Carbon::now();

            $nextSlotTime = $now->copy()->addMinutes($slotDuration - ($now->minute % $slotDuration))->second(0);

            if ($nextSlotTime->gt($current)) {
                $current = $nextSlotTime;
            }
        }

        while ($current->copy()->addMinutes($serviceDuration)->lte($endTime)) {
            $slotEnd = $current->copy()->addMinutes($serviceDuration);

            if ($this->isSlotAvailable($current, $slotEnd, $unavailabilities, $bookings)) {
                $slots[] = [
                    'start_time' => $current->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'duration_minutes' => $serviceDuration,
                ];
            }

            $current->addMinutes($slotDuration);
        }

        return $slots;
    }

    private function isSlotAvailable(Carbon $slotStart, Carbon $slotEnd, $unavailabilities, $bookings): bool
    {
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

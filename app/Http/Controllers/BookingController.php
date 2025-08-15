<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Bookings;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request;

        $barber = Barber::findOrFail($validated['barber_id']);
        $service = Service::findOrFail($validated['service_id']);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

        if (! $this->isSlotAvailable($barber, $startTime, $endTime)) {
            return response()->json([
                'message' => 'The selected time slot is not available',
                'errors' => [
                    'start_time' => ['The selected time slot conflicts with existing bookings or unavailable hours'],
                ],
            ], 422);
        }

        $booking = Bookings::create([
            'barber_id' => $validated['barber_id'],
            'service_id' => $validated['service_id'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'status' => 'confirmed',
            'notes' => $validated['notes'] ?? null,
        ]);

        $booking->load(['barber.user', 'service']);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => [
                'id' => $booking->id,
                'barber_name' => $booking->barber->user->name,
                'service_name' => $booking->service->name,
                'start_time' => $booking->start_time->format('Y-m-d H:i'),
                'end_time' => $booking->end_time->format('Y-m-d H:i'),
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'customer_phone' => $booking->customer_phone,
                'status' => $booking->status,
                'notes' => $booking->notes,
            ],
        ], 201);
    }

    private function isSlotAvailable(Barber $barber, Carbon $startTime, Carbon $endTime): bool
    {
        $date = $startTime->copy()->startOfDay();
        $dayOfWeek = $startTime->format('l');

        $workingHours = $barber->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $workingHours) {
            return false;
        }

        $workingStart = $date->copy()->setTimeFromTimeString($workingHours->start_time->format('H:i'));
        $workingEnd = $date->copy()->setTimeFromTimeString($workingHours->end_time->format('H:i'));

        if ($startTime->lt($workingStart) || $endTime->gt($workingEnd)) {
            return false;
        }

        if ($startTime->isPast()) {
            return false;
        }

        $conflictingBookings = $barber->bookings()
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        if ($conflictingBookings) {
            return false;
        }

        $conflictingUnavailabilities = $barber->unavailabilities()
            ->whereDate('start_time', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        return ! $conflictingUnavailabilities;
    }
}

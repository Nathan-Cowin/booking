<?php

namespace App\Http\Controllers;

use App\Contracts\BarberRepositoryInterface;
use App\Enums\BookingStatus;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Barber;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function __construct(
        private readonly BarberRepositoryInterface $barberRepository
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $client = auth()->user()->client;

        if (! $client) {
            return BookingResource::collection([]);
        }

        $allBookings = $client->bookings()
            ->with(['barber.user', 'services'])
            ->orderBy('start_time', 'desc')
            ->get();

        return BookingResource::collection($allBookings);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $client = $user->client;

        if (! $client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 422);
        }

        $barber = Barber::findOrFail($validated['barber_id']);
        $startTime = Carbon::parse($validated['start_time']);

        $totalDuration = $this->barberRepository->serviceDuration($barber, $validated['service_ids']);
        $endTime = $startTime->copy()->addMinutes($totalDuration);

        if (! $this->isSlotAvailable($barber, $startTime, $endTime)) {
            return response()->json([
                'message' => 'The selected time slot is not available',
                'errors' => [
                    'start_time' => ['The selected time slot conflicts with existing bookings or unavailable hours'],
                ],
            ], 422);
        }
        logger('ere');

        $booking = Booking::create([
            'barber_id' => $validated['barber_id'],
            'client_id' => $client->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => BookingStatus::Confirmed,
            'notes' => $validated['notes'] ?? null,
        ]);

        logger('here');

        $booking->services()->attach($validated['service_ids']);

        $booking->load(['barber.user', 'services', 'client']);

        logger('final');
        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => new BookingResource($booking),
        ], 201);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $user = auth()->user();
        $client = $user->client;

        if (! $client) {
            return response()->json([
                'message' => 'Client profile not found',
            ], 422);
        }

        // Check if booking belongs to the authenticated client
        if ($booking->client_id !== $client->id) {
            return response()->json([
                'message' => 'Unauthorized to cancel this booking',
            ], 403);
        }

        // Check if booking can be cancelled (only upcoming bookings)
        if (! $booking->isUpcoming()) {
            return response()->json([
                'message' => 'Only upcoming bookings can be cancelled',
            ], 422);
        }

        $booking->update(['status' => BookingStatus::Cancelled]);
        $booking->load(['barber.user', 'services']);

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => new BookingResource($booking),
        ]);
    }

    private function isSlotAvailable(Barber $barber, Carbon $startTime, Carbon $endTime): bool
    {
        $date = $startTime->copy()->startOfDay();
        $dayOfWeek = $startTime->format('w');

        $workingHours = $barber->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $workingHours) {
            return false;
        }

        $workingStart = $date->copy()->setTimeFromTimeString($workingHours->start_time);
        $workingEnd = $date->copy()->setTimeFromTimeString($workingHours->end_time);

        if ($startTime->lt($workingStart) || $endTime->gt($workingEnd)) {
            return false;
        }

        if ($startTime->isPast()) {
            return false;
        }

        dd('here');

        $conflictingBookings = $barber->bookings()
            ->whereDate('start_time', $date)
            ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::NoShow])
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

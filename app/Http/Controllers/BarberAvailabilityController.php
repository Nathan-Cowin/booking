<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarberAvailabilityRequest;
use App\Models\Barber;
use App\Services\BarberAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class BarberAvailabilityController extends Controller
{
    public function __construct(
        private readonly BarberAvailabilityService $availabilityService
    ) {}

    public function index(BarberAvailabilityRequest $request, Barber $barber): JsonResponse
    {
        $date = Carbon::parse($request->date)->startOfDay();
        $totalDuration = $this->availabilityService->calculateTotalServiceDuration($barber, $request->service_ids);
        $slots = $this->availabilityService->availableSlots($barber, $date, $request->service_ids);

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'barber_id' => $barber->id,
            'barber_name' => $barber->user->name,
            'total_duration_minutes' => $totalDuration,
            'available_slots' => $slots,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarberAvailabilityRequest;
use App\Http\Resources\BarberAvailabilityResource;
use App\Models\Barber;
use App\Services\BarberAvailabilityService;
use Carbon\Carbon;

class BarberAvailabilityController extends Controller
{
    public function __construct(
        private readonly BarberAvailabilityService $availabilityService
    ) {}

    public function index(BarberAvailabilityRequest $request, Barber $barber): BarberAvailabilityResource
    {
        $date = Carbon::parse($request->date)->startOfDay();
        $totalDuration = $this->calculateTotalServiceDuration($barber, $request->service_ids);
        $slots = $this->availabilityService->availableSlots($barber, $date, $request->service_ids);

        $data = [
            'date' => $date->format('Y-m-d'),
            'barber_id' => $barber->id,
            'barber_name' => $barber->user->name,
            'total_duration_minutes' => $totalDuration,
            'available_slots' => $slots,
        ];

        return new BarberAvailabilityResource($data);
    }

    private function calculateTotalServiceDuration(Barber $barber, array $serviceIds): int
    {
        return $barber->services()
            ->whereIn('services.id', $serviceIds)
            ->sum('duration_minutes');
    }
}

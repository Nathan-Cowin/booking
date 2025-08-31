<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarberAvailabilityRequest;
use App\Services\BarberAvailabilityService;
use App\Services\BarberServiceCompatibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MultiBarberAvailabilityController extends Controller
{
    public function __construct(
        private readonly BarberAvailabilityService $availabilityService,
        private readonly BarberServiceCompatibilityService $compatibilityService
    ) {}

    public function index(BarberAvailabilityRequest $request): JsonResponse
    {
        $date = Carbon::parse($request->date)->startOfDay();
        $serviceIds = $request->service_ids;

        // Find all barbers that can provide the requested services
        $compatibleBarbers = $this->compatibilityService->findCompatibleBarbers($serviceIds);

        if ($compatibleBarbers->isEmpty()) {
            return response()->json([
                'date' => $date->format('Y-m-d'),
                'compatible_barbers' => [],
                'available_slots' => [],
                'message' => 'No barbers offer this combination of services'
            ]);
        }

        $allSlots = [];
        
        foreach ($compatibleBarbers as $barber) {
            $totalDuration = $this->availabilityService->serviceDuration($barber, $serviceIds);
            $slots = $this->availabilityService->availableSlots($barber, $date, $serviceIds);

            // Add barber info to each slot
            foreach ($slots as &$slot) {
                $slot['barber_id'] = $barber->id;
                $slot['barber_name'] = $barber->user->name;
            }

            $allSlots = array_merge($allSlots, $slots);
        }

        // Sort all slots by start time
        usort($allSlots, function ($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'compatible_barbers' => $compatibleBarbers->map(function ($barber) {
                return [
                    'id' => $barber->id,
                    'name' => $barber->user->name,
                ];
            })->toArray(),
            'available_slots' => $allSlots,
        ]);
    }
}
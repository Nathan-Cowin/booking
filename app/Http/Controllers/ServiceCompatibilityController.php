<?php

namespace App\Http\Controllers;

use App\Services\BarberServiceCompatibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCompatibilityController extends Controller
{
    public function __construct(
        private readonly BarberServiceCompatibilityService $compatibilityService
    ) {}

    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', Rule::exists('services', 'id')],
        ]);

        $serviceIds = $request->service_ids;
        
        $compatibleBarbers = $this->compatibilityService->findCompatibleBarbers($serviceIds);
        $hasCompatibleBarbers = $compatibleBarbers->isNotEmpty();

        return response()->json([
            'compatible' => $hasCompatibleBarbers,
            'compatible_barber_count' => $compatibleBarbers->count(),
            'compatible_barbers' => $compatibleBarbers->map(function ($barber) {
                return [
                    'id' => $barber->id,
                    'name' => $barber->user->name,
                ];
            })->toArray(),
            'message' => $hasCompatibleBarbers 
                ? "Found {$compatibleBarbers->count()} barber(s) offering these services"
                : 'No barbers offer this combination of services'
        ]);
    }
}
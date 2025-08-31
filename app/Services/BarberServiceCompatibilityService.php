<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class BarberServiceCompatibilityService
{
    /**
     * Find all barbers that offer all the requested services
     *
     * @param array<int> $serviceIds
     * @return Collection<int, Barber>
     */
    public function findCompatibleBarbers(array $serviceIds): Collection
    {
        if (empty($serviceIds)) {
            return new Collection();
        }

        return Barber::whereHas('services', function ($query) use ($serviceIds) {
            $query->whereIn('services.id', $serviceIds);
        }, '=', count($serviceIds))
        ->with(['user', 'services'])
        ->get()
        ->filter(function (Barber $barber) use ($serviceIds) {
            // Double-check that the barber has ALL requested services
            $barberServiceIds = $barber->services->pluck('id')->toArray();
            return count(array_intersect($serviceIds, $barberServiceIds)) === count($serviceIds);
        });
    }

    /**
     * Check if any barbers offer all the requested services
     */
    public function hasCompatibleBarbers(array $serviceIds): bool
    {
        return $this->findCompatibleBarbers($serviceIds)->isNotEmpty();
    }

    /**
     * Get services that no barber offers together with the other selected services
     *
     * @param array<int> $serviceIds
     * @return Collection<int, Service>
     */
    public function getIncompatibleServices(array $serviceIds): Collection
    {
        if (empty($serviceIds)) {
            return new Collection();
        }

        $compatibleBarbers = $this->findCompatibleBarbers($serviceIds);
        
        if ($compatibleBarbers->isNotEmpty()) {
            return new Collection(); // All services are compatible
        }

        // Find which services are causing the incompatibility
        $incompatibleServices = new Collection();
        
        foreach ($serviceIds as $serviceId) {
            $otherServices = array_filter($serviceIds, fn($id) => $id !== $serviceId);
            
            if (empty($otherServices)) {
                continue;
            }
            
            $barbersWithoutThisService = $this->findCompatibleBarbers($otherServices);
            
            if ($barbersWithoutThisService->isNotEmpty()) {
                $incompatibleServices->push(Service::find($serviceId));
            }
        }

        return $incompatibleServices->filter();
    }
}
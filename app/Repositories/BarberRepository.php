<?php

namespace App\Repositories;

use App\Contracts\BarberRepositoryInterface;
use App\Models\Barber;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BarberRepository implements BarberRepositoryInterface
{
    public function workingHours(Barber $barber, Carbon $date): ?WorkingHours
    {
        return $barber->workingHours()
            ->where('day_of_week', $date->format('l'))
            ->where('is_available', true)
            ->first();
    }

    public function unavailabilities(Barber $barber, Carbon $date): Collection
    {
        return $barber->unavailabilities()
            ->whereDate('start_time', $date)
            ->get(['start_time', 'end_time']);
    }

    public function existingBookings(Barber $barber, Carbon $date): Collection
    {
        return $barber->bookings()
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->get(['start_time', 'end_time']);
    }

    public function calculateTotalServiceDuration(Barber $barber, array $serviceIds): int
    {
        return $barber->services()
            ->whereIn('services.id', $serviceIds)
            ->sum('duration_minutes');
    }
}

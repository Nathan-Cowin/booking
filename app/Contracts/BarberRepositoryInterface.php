<?php

namespace App\Contracts;

use App\Models\Barber;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface BarberRepositoryInterface
{
    public function workingHours(Barber $barber, Carbon $date): ?WorkingHours;

    public function unavailabilities(Barber $barber, Carbon $date): Collection;

    public function existingBookings(Barber $barber, Carbon $date): Collection;

    public function serviceDuration(Barber $barber, array $serviceIds): int;
}

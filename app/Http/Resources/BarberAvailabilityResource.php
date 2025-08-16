<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarberAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'barber_id' => $this->resource['barber_id'],
            'barber_name' => $this->resource['barber_name'],
            'total_duration_minutes' => $this->resource['total_duration_minutes'],
            'available_slots' => $this->resource['available_slots'],
        ];
    }
}

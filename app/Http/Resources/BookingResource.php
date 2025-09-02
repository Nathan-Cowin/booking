<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barber_name' => $this->barber->user->name,
            'services' => $this->services->pluck('name'),
            'service_details' => ServiceResource::collection($this->services),
            'total_cost' => $this->services->sum('price'),
            'start_time' => $this->start_time->format('Y-m-d H:i'),
            'end_time' => $this->end_time->format('Y-m-d H:i'),
            'status' => $this->status,
            'is_upcoming' => $this->isUpcoming(),
            'is_past' => $this->isPast(),
            'is_cancelled' => $this->isCancelled(),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

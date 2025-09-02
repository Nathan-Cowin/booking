<?php

namespace App\Models;

use Database\Factories\BarberServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BarberService extends Pivot
{
    /** @use HasFactory<BarberServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'price',
        'duration_minutes',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

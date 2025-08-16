<?php

namespace App\Models;

use Database\Factories\BarberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
    /** @use HasFactory<BarberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->using(BarberService::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(Unavailability::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHours::class);
    }
}

<?php

namespace App\Models;

use App\Enums\ServiceTypeEnum;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'duration_minutes',
        'price',
    ];

    public function casts()
    {
        return [
            'type' => ServiceTypeEnum::class,
        ];
    }

    public function barbers(): BelongsToMany
    {
        return $this->belongsToMany(Barber::class)
            ->using(BarberService::class);
    }
}

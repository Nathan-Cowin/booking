<?php

namespace App\Models;

use Database\Factories\BarberStyleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BarberStyle extends Pivot
{
    /** @use HasFactory<BarberStyleFactory> */
    use HasFactory;
}

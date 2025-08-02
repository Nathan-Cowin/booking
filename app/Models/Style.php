<?php

namespace App\Models;

use Database\Factories\StyleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    /** @use HasFactory<StyleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}

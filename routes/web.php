<?php

use App\Http\Controllers\BarberController;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Models\Tenant;

Route::get('/test', [BarberController::class, 'index']);

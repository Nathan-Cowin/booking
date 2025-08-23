<?php

use App\Http\Controllers\BarberAvailabilityController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\BarberServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClientAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/client/register', [ClientAuthController::class, 'register']);
Route::post('/client/login', [ClientAuthController::class, 'login'])->name('login');

Route::apiResource('/barbers', BarberController::class)->only('index');
Route::apiResource('/barbers.services', BarberServiceController::class)->only('index');
Route::get('/barbers/{barber}/availability', [BarberAvailabilityController::class, 'index']);

//move below to middleware
//Route::post('/bookings', [BookingController::class, 'store']);
Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/client/logout', [ClientAuthController::class, 'logout']);
    Route::get('/client/me', [ClientAuthController::class, 'me']);
    Route::post('/bookings', [BookingController::class, 'store']);
});

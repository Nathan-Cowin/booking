<?php

use App\Http\Controllers\BarberAvailabilityController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\BarberServiceController;
use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('/barbers', BarberController::class)->only('index');
Route::apiResource('/barbers.services', BarberServiceController::class)->only('index');
Route::get('/barbers/{barber}/availability', [BarberAvailabilityController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);

Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

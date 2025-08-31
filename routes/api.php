<?php

use App\Http\Controllers\BarberAvailabilityController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\BarberServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\MultiBarberAvailabilityController;
use App\Http\Controllers\ServiceCompatibilityController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/client/register', [ClientAuthController::class, 'register']);
Route::post('/client/login', [ClientAuthController::class, 'login'])->name('login');

Route::apiResource('/barbers', BarberController::class)->only('index');
Route::apiResource('/barbers.services', BarberServiceController::class)->only('index');
Route::apiResource('/services', ServiceController::class)->only('index');
Route::get('/barbers/{barber}/availability', [BarberAvailabilityController::class, 'index']);
Route::get('/availability', [MultiBarberAvailabilityController::class, 'index']);
Route::post('/services/compatibility', [ServiceCompatibilityController::class, 'check']);

//move below to middleware
//Route::post('/bookings', [BookingController::class, 'store']);
Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/client/logout', [ClientAuthController::class, 'logout']);
    Route::get('/client/me', [ClientAuthController::class, 'me']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});

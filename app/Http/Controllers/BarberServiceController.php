<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use Illuminate\Http\JsonResponse;

class BarberServiceController extends Controller
{
    public function index(Barber $barber): JsonResponse
    {
        return response()->json($barber->services);
    }
}

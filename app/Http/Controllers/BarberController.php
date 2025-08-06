<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    public function index()
    {
        return Barber::with('user')->get();
    }
}

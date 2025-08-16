<?php

namespace App\Http\Controllers;

use App\Http\Resources\BarberResource;
use App\Models\Barber;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarberController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BarberResource::collection(Barber::with(['user'])->get());
    }
}

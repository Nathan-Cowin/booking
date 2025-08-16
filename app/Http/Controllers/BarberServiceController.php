<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Barber;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarberServiceController extends Controller
{
    public function index(Barber $barber): AnonymousResourceCollection
    {
        return ServiceResource::collection($barber->services);
    }
}

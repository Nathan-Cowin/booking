<?php

namespace App\Http\Controllers;

use App\Models\Barber;

//todo: think of correct name of controller
//todo: going to call it until we get first response in FE
class BarberAvailibiltyController extends Controller
{
    //get avavilibilty by date
    public function index($request, Barber $barber)
    {
        $request->date;

        //get service(s) duration

        //get unavailbilites (this will be lunch breaks etc..)

        //get the existing bookings

        //figure out what slots are availblie

        return $slots;
    }
}

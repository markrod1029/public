<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    //

    public function index(){

        $location = Location::query()->get();
        return response()->json($location);
    }
}

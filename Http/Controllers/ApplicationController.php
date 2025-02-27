<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    //

    function __invoke() {

        return view('app');
    }
}

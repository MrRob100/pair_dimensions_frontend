<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function lwc()
    {
        return view('test-lwc');
    }
}

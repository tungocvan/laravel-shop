<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public function index()
    {
        return view('Website::cart.index');
    }
}

<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {

        // Trả về View Blade bình thường
        return view('Admin::pages.dashboard');
       // return view('Website::pages.dashboard');
    }
}

<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class WebsiteDashboardController extends Controller
{
    public function index()
    {
        return view('Website::pages.admin.dashboard.index');
    }
}

<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class WebsiteSettingsController extends Controller
{
    public function index()
    {
        return view('Website::pages.admin.settings.index');
    }
}

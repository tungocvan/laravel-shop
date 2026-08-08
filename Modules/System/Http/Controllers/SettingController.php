<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function index()
    {
        return view('System::pages.settings.index');
    }
    public function profile()
    {
        return view('System::pages.settings.profile');
    }
    public function modules()
    {
        return view('System::pages.settings.modules');
    }
}

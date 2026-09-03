<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function index()
    {
        return view('System::pages.settings.index');
    }

    public function loginTheme()
    {
        return view('System::pages.settings.login-theme');
    }

    public function loginRedirect()
    {
        return view('System::pages.settings.login-redirect');
    }

    public function profile()
    {
        return view('System::pages.settings.profile');
    }

    public function modules()
    {
        return view('System::pages.settings.modules');
    }

    public function artisan()
    {
        return view('System::pages.settings.artisan');
    }

    public function scripts()
    {
        return view('System::pages.settings.scripts');
    }
}
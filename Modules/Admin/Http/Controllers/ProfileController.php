<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{

    public function profile()
    {
        return view('Admin::pages.profiles.profile');
    }

}

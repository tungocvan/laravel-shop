<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class HeaderController extends Controller
{
    public function index()
    {
        return view('Admin::pages.header.index');
    }
}

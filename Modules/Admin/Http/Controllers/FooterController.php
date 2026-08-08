<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class FooterController extends Controller
{
    public function index()
    {
        return view('Admin::pages.footer.index');
    }
}

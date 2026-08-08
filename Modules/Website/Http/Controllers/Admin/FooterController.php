<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class FooterController extends Controller
{
    public function index()
    {
        return view('Website::pages.admin.footer.index');
    }
}

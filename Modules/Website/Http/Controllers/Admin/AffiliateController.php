<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AffiliateController extends Controller
{
    public function index()
    {
        return view('Website::pages.admin.affiliate.index');
    }
}

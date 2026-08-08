<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class AffiliateController extends Controller
{
    public function index()
    {
        return view('Admin::pages.affiliate.index');
    }
}
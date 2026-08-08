<?php

namespace Modules\Website\Http\Controllers\Admin;

use Illuminate\Routing\Controller;

class CouponController extends Controller
{
    public function index()
    {
        // Theo yêu cầu: View nằm trong folder pages
        return view('Website::pages.admin.coupons.index');
    }

    public function create()
    {
        return view('Website::pages.admin.coupons.create');
    }

    public function edit($id)
    {
        return view('Website::pages.admin.coupons.edit', compact('id'));
    }
}

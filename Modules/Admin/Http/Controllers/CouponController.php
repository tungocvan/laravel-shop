<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Routing\Controller;

class CouponController extends Controller
{
    public function index()
    {
        // Theo yêu cầu: View nằm trong folder pages
        return view('Admin::pages.coupons.index');
    }

    public function create()
    {
        return view('Admin::pages.coupons.create');
    }

    public function edit($id)
    {
        return view('Admin::pages.coupons.edit', compact('id'));
    }
}
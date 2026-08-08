<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HomeSettingsController extends Controller
{
    // 1. CONTROLLER_ACTION
    public function index()
    {
        // Trả về view nằm trong thư mục pages/home
        return view('Admin::pages.home.index', [
            'title' => 'Cấu hình Trang chủ'
        ]);
    }
    // End 1.
}

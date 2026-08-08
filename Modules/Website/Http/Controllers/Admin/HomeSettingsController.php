<?php

namespace Modules\Website\Http\Controllers\Admin;

// use Illuminate\Contracts\Support\Renderable;
// use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HomeSettingsController extends Controller
{
    // 1. CONTROLLER_ACTION
    public function index()
    {
        // Trả về view nằm trong thư mục pages/home
        return view('Website::pages.admin.home.index', [
            'title' => 'Cấu hình Trang chủ'
        ]);
    }
    // End 1.
}

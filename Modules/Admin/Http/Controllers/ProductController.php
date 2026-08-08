<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function __construct()
    {
        // 1. Phân quyền XEM (Index, Show)
        // Áp dụng cho hàm index và show
        $this->middleware('permission:view_product')->only(['index', 'show']);
        
        // // 2. Phân quyền THÊM (Create, Store)
        // // Áp dụng cho hàm create (hiện form) và store (lưu data)
        $this->middleware('permission:create_product')->only(['create', 'store']);

        // // 3. Phân quyền SỬA (Edit, Update)
        $this->middleware('permission:edit_product')->only(['edit', 'update']);

        // // 4. Phân quyền XÓA (Destroy)
        $this->middleware('permission:delete_product')->only(['destroy']);
    }
    // Danh sách sản phẩm
    public function index()
    {
        return view('Admin::pages.products.index');
    }

    // Trang thêm mới
    public function create()
    {
        return view('Admin::pages.products.create');
    }

    // Trang chỉnh sửa
    public function edit($id)
    {
        return view('Admin::pages.products.edit', compact('id'));
    }
}

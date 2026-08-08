<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    public function __construct()
    {
        // 1. Phân quyền XEM (Index, Show)
        // Áp dụng cho hàm index và show
        $this->middleware('permission:view_customer')->only(['index', 'show']);

        // // 2. Phân quyền THÊM (Create, Store)
        // // Áp dụng cho hàm create (hiện form) và store (lưu data)
        $this->middleware('permission:create_customer')->only(['create', 'store']);

        // // 3. Phân quyền SỬA (Edit, Update)
        $this->middleware('permission:edit_customer')->only(['edit', 'update']);

        // // 4. Phân quyền XÓA (Destroy)
        $this->middleware('permission:delete_customer')->only(['destroy']);
    }

    public function index()
    {
        return view('Admin::pages.customers.index');
    }

    // Các hàm create, edit, show sẽ làm ở bước sau (CustomerDetail)
    public function show($id)
    {
        return view('Admin::pages.customers.show', compact('id')); // Truyền ID sang View
    }
    public function create()
    {
        return view('Admin::pages.customers.create');
    }
}

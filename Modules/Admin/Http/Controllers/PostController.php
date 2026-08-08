<?php

namespace Modules\Admin\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
 
class PostController extends Controller
{
  
    /**
     * Danh sách bài viết
     */
    public function index()
    {
        return view('Admin::pages.posts.index');
    }

    /**
     * Trang thêm mới
     */
    public function create()
    {
        return view('Admin::pages.posts.create');
    }

    /**
     * Trang chỉnh sửa
     */
    public function edit($id)
    {
        return view('Admin::pages.posts.edit', compact('id'));
    }
}
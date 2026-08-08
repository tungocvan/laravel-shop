<?php

namespace Modules\Post\Http\Controllers;


use Illuminate\Routing\Controller;

class PostController extends Controller
{

    /**
     * Danh sách bài viết
     */
    public function index()
    {
        return view('Post::pages.posts.index');
    }

    /**
     * Trang thêm mới
     */
    public function create()
    {
        return view('Post::pages.posts.create');
    }

    /**
     * Trang chỉnh sửa
     */
    public function edit($id)
    {
        return view('Post::pages.posts.edit', compact('id'));
    }
}

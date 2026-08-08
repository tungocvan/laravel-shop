<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index() {
        return view('Admin::pages.categories.index');
    }
    public function create() {
        return view('Admin::pages.categories.create');
    }
    public function edit($id) {
        return view('Admin::pages.categories.edit', compact('id'));
    }
}

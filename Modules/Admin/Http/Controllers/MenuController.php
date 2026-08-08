<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class MenuController extends Controller
{
    public function index()
    {
        return view('Admin::pages.menus.index');
    }

    public function create()
    {
        return view('Admin::pages.menus.create');
    }

    public function edit($id)
    {
        return view('Admin::pages.menus.edit', compact('id'));
    }
}

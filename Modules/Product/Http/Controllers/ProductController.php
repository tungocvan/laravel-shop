<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        return view('product::pages.products.index');
    }

    public function create()
    {
        return view('product::pages.products.create');
    }

    public function edit($id)
    {
        return view('product::pages.products.edit', ['id' => (int) $id]);
    }
}

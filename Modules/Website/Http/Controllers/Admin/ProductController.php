<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        return view('Website::admin.products.index');
    }
}

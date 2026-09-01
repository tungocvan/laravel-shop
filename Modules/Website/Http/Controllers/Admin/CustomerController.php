<?php

namespace Modules\Website\Http\Controllers\Admin;

use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    public function index()
    {
        return view('Website::pages.admin.customers.index');
    }

    public function show($id)
    {
        return view('Website::pages.admin.customers.show', compact('id'));
    }

    public function create()
    {
        return view('Website::pages.admin.customers.create');
    }
}

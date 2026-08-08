<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return view('User::pages.staff.index');
    }

    public function create()
    {
        return view('User::pages.staff.create');
    }

    public function edit(int $id)
    {
        return view('User::pages.staff.edit', compact('id'));
    }
}

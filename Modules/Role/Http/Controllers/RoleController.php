<?php

namespace Modules\Role\Http\Controllers;
use Illuminate\Routing\Controller;

class RoleController extends Controller {
    public function index() { return view('Role::pages.roles.index'); }
    public function create() { return view('Role::pages.roles.create'); }
    public function edit($id) { return view('Role::pages.roles.edit', compact('id')); }
}
<?php
namespace Modules\Admin\Http\Controllers;
use Illuminate\Routing\Controller;

class RoleController extends Controller {
    public function index() { return view('Admin::pages.roles.index'); }
    public function create() { return view('Admin::pages.roles.create'); }
    public function edit($id) { return view('Admin::pages.roles.edit', compact('id')); }
}
<?php
namespace Modules\Admin\Http\Controllers;
use Illuminate\Routing\Controller;

class StaffController extends Controller {
    public function index() { return view('Admin::pages.staff.index'); }
    public function create() { return view('Admin::pages.staff.create'); }
    public function edit($id) { return view('Admin::pages.staff.edit', compact('id')); }
}
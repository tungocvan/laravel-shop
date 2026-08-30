<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('Admin::admin');
    }

    public function themes()
    {
        return view('Admin::pages.admin.themes');
    }

    public function layout()
    {
        return view('Admin::pages.admin.layout');
    }

    public function layoutGeneral()
    {
        return $this->layoutSection('general', 'Layout tổng thể');
    }

    public function layoutHeader()
    {
        return $this->layoutSection('header', 'Header');
    }

    public function layoutSidebar()
    {
        return $this->layoutSection('sidebar', 'Sidebar');
    }

    public function layoutFooter()
    {
        return $this->layoutSection('footer', 'Footer');
    }

    public function layoutDesign()
    {
        return $this->layoutSection('design', 'Giao diện & Theme');
    }

    public function layoutNavigation()
    {
        return $this->layoutSection('navigation', 'Navigation');
    }

    private function layoutSection(string $section, string $title)
    {
        return view('Admin::pages.admin.layout-section', compact('section', 'title'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}

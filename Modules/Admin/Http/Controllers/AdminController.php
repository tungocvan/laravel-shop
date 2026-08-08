<?php

namespace Modules\Admin\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        //  $this->middleware('permission:admin-list|admin-create|admin-edit|admin-delete', ['only' => ['index','show']]);
        //  $this->middleware('permission:admin-create', ['only' => ['create','store']]);
        //  $this->middleware('permission:admin-edit', ['only' => ['edit','update']]);
        //  $this->middleware('permission:admin-delete', ['only' => ['destroy']]);
    }
    public function index()
    {
        return view('Admin::admin');
    }
    public function adminHeader()
    {
        return view('Admin::pages.admin.header.index');
    }
    public function themes()
    {
        return view('Admin::pages.admin.themes');
    }

    public function layout()
    {
        return view('Admin::pages.admin.layout');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

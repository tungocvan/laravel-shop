<?php

namespace Modules\Website\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        //  $this->middleware('permission:website-list|website-create|website-edit|website-delete', ['only' => ['index','show']]);
        //  $this->middleware('permission:website-create', ['only' => ['create','store']]);
        //  $this->middleware('permission:website-edit', ['only' => ['edit','update']]);
        //  $this->middleware('permission:website-delete', ['only' => ['destroy']]);
    }
    public function home()
    {

        return view('Website::pages.home.index');
    }
    public function help()
    {
        return view('Website::pages.help.index');
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

<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;

class SupplierTrackingController extends Controller
{
    public function index()
    {
        return view('Pharma::pages.supplier-trackings.index');
    }

    public function create() 
    {
        return view('Pharma::pages.supplier-trackings.create');
    }

    public function edit(int $id)
    {
        return view('Pharma::pages.supplier-trackings.edit', [
            'id' => $id,
        ]);
    }

    public function show(int $id)
    {
        return view('Pharma::pages.supplier-trackings.show', [
            'id' => $id,
        ]);
    }
}
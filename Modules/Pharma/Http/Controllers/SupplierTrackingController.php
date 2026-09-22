<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierTrackingController extends Controller
{
    public function index()
    {
        return view('Pharma::pages.supplier-trackings.index');
    }

    public function create(Request $request)
    {
        return view('Pharma::pages.supplier-trackings.create', [
            'medicineId' => $request->integer('medicine_id') ?: null,
        ]);
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
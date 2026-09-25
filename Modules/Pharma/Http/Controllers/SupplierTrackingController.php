<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pharma\Models\SupplierTracking;

class SupplierTrackingController extends Controller
{
    public function index()
    {
        return view('Pharma::pages.supplier-trackings.index');
    }

    public function create(Request $request)
    {
        $medicineId = $request->integer('medicine_id') ?: null;
        $existingTrackingId = $medicineId
            ? SupplierTracking::query()->where('medicine_id', $medicineId)->latest('id')->value('id')
            : null;

        return view('Pharma::pages.supplier-trackings.create', [
            'medicineId' => $medicineId,
            'existingTrackingId' => $existingTrackingId ? (int) $existingTrackingId : null,
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
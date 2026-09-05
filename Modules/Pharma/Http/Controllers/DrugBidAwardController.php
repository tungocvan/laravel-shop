<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DrugBidAwardController extends Controller
{
    public function index(): View
    {
        return view('Pharma::pages.drug-bid-award.index');
    }

    public function create(): View
    {
        return view('Pharma::pages.drug-bid-award.create');
    }

    public function edit(int $id): View
    {
        return view('Pharma::pages.drug-bid-award.edit', compact('id'));
    }

    public function allocations(int $id): View
    {
        return view('Pharma::pages.drug-bid-award.allocations', compact('id'));
    }
}

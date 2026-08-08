<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PriceListController extends Controller
{
    public function create(): View
    {
        return view('Pharma::pages.price-list.create');
    }
}

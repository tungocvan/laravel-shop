<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;

class ProductCommissionController extends Controller
{
    public function index($productId)
    {
        return view('product::pages.affiliate.product-commissions', [
            'productId' => (int) $productId,
        ]);
    }
}

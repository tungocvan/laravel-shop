<?php
namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Website\Models\WpProduct;

class ProductCommissionController extends Controller
{
    public function index($productId)
    {
        $product = WpProduct::findOrFail($productId);
        
        return view('Admin::pages.affiliate.product-commissions', [
            'product' => $product
        ]);
    }
}
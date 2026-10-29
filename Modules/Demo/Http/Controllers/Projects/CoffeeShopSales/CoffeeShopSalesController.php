<?php

namespace Modules\Demo\Http\Controllers\Projects\CoffeeShopSales;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CoffeeShopSalesController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.coffee-shop-sales.index');
    }
}

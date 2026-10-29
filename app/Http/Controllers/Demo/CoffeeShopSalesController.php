<?php
namespace App\Http\Controllers\Demo;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class CoffeeShopSalesController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.quan-ly-ban-hang-ca-phe.index');
    }
}

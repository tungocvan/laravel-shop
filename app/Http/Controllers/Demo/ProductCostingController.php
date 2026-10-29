<?php
namespace App\Http\Controllers\Demo;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class ProductCostingController extends Controller
{
    public function __invoke(): View { return view('demo.tinh-gia-thanh-san-pham.index'); }
}

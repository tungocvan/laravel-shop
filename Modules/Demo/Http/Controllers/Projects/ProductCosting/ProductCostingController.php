<?php
namespace Modules\Demo\Http\Controllers\Projects\ProductCosting;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class ProductCostingController extends Controller
{
    public function __invoke(): View { return view('demo::projects.product-costing.index'); }
}

<?php
namespace Modules\Website\Http\Controllers\Admin;
use Illuminate\Routing\Controller;

class FlashSaleController extends Controller {
    public function index() {
        return view('Website::pages.admin.flash-sale.index'); // Wrapper View
    }
}

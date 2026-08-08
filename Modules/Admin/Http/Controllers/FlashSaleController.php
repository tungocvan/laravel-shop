<?php
namespace Modules\Admin\Http\Controllers;
use Illuminate\Routing\Controller;

class FlashSaleController extends Controller {
    public function index() {
        return view('Admin::pages.flash-sale.index'); // Wrapper View
    }
}

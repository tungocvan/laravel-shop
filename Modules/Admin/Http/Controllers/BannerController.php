<?php
namespace Modules\Admin\Http\Controllers;
use Illuminate\Routing\Controller;

class BannerController extends Controller {
    public function index() {
        return view('Admin::pages.banner.index'); // Wrapper View
    }
}

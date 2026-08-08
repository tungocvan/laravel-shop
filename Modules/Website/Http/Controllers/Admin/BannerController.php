<?php
namespace Modules\Website\Http\Controllers\Admin;
use Illuminate\Routing\Controller;

class BannerController extends Controller {
    public function index() {
        return view('Website::pages.admin.banner.index'); // Wrapper View
    }
}

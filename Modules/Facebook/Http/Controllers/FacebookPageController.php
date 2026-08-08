<?php

namespace Modules\Facebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FacebookPageController extends Controller
{
    public function index(): View
    {
        return view('Facebook::pages.pages.index');
    }
}

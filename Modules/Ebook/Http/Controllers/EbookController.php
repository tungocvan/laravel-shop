<?php

namespace Modules\Ebook\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class EbookController extends Controller
{
    public function index(): View
    {
        return view('Ebook::pages.ebook.index');
    }
}

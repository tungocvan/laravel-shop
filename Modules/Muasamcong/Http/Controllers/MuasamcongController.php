<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class MuasamcongController extends Controller
{
    public function index(): View
    {
        return view('Muasamcong::muasamcong');
    }

    public function contractors(): View
    {
        return view('Muasamcong::contractors');
    }

    public function hsmt(): View
    {
        return view('Muasamcong::hsmt');
    }

    public function config(): View
    {
        return view('Muasamcong::config');
    }
}

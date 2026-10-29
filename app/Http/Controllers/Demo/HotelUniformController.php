<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class HotelUniformController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.quan-ly-dong-phuc-khach-san.index');
    }
}

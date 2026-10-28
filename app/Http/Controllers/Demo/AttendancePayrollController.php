<?php

namespace App\Http\Controllers\Demo;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AttendancePayrollController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.cham-cong-tinh-luong.index');
    }
}

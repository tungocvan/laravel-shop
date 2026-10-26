<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SalesEmployeesController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.sales-employees.index');
    }
}

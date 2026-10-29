<?php

namespace Modules\Demo\Http\Controllers\Projects\SalesEmployees;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

class SalesEmployeesController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.sales-employees.index');
    }
}

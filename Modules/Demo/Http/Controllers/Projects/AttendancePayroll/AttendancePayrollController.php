<?php

namespace Modules\Demo\Http\Controllers\Projects\AttendancePayroll;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AttendancePayrollController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.attendance-payroll.index');
    }
}

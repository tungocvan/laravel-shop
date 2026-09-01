<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AttendanceRecordsController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(auth('admin')->check(), 403);

        return view('Attendance::admin.records');
    }
}

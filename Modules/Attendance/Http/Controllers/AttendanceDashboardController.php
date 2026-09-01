<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Attendance\Services\AttendanceDashboardService;

class AttendanceDashboardController extends Controller
{
    public function __invoke(AttendanceDashboardService $dashboard): View
    {
        abort_unless(auth('admin')->check(), 403);

        return view('Attendance::admin.dashboard', [
            'dashboard' => $dashboard->summary(),
        ]);
    }
}

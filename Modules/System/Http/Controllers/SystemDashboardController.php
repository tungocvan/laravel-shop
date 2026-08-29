<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\System\Services\SystemDashboardService;

final class SystemDashboardController extends Controller
{
    public function __invoke(SystemDashboardService $dashboard): View
    {
        $admin = auth('admin')->user();

        abort_unless($admin?->can('system.manage'), 403);

        return view('System::pages.dashboard', [
            'dashboard' => $dashboard->forUser($admin),
        ]);
    }
}

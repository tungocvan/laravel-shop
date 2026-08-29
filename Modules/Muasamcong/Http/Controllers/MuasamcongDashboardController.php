<?php

namespace Modules\Muasamcong\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Muasamcong\Services\MuasamcongDashboardService;

final class MuasamcongDashboardController extends Controller
{
    public function __invoke(MuasamcongDashboardService $dashboard): View
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null, 403);

        return view('Muasamcong::dashboard', [
            'dashboard' => $dashboard->forUser($admin),
        ]);
    }
}

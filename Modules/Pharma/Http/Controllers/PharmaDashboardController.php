<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Pharma\Services\PharmaDashboardService;

final class PharmaDashboardController extends Controller
{
    public function __invoke(PharmaDashboardService $dashboard): View
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null, 403);

        return view('Pharma::pages.dashboard', [
            'dashboard' => $dashboard->forUser($admin),
        ]);
    }
}

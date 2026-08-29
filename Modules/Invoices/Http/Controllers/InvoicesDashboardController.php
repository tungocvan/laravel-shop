<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Invoices\Services\InvoiceDashboardService;

final class InvoicesDashboardController extends Controller
{
    public function __invoke(InvoiceDashboardService $dashboard): View
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null, 403);

        return view('Invoices::pages.invoices.dashboard', [
            'dashboard' => $dashboard->forUser($admin),
        ]);
    }
}

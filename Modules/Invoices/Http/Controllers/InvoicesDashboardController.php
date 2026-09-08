<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Invoices\Services\InvoiceDashboardService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;

final class InvoicesDashboardController extends Controller
{
    public function __invoke(InvoiceDashboardService $dashboard, GoogleDriveConnectionService $drive): View
    {
        $admin = auth('admin')->user();

        abort_unless($admin !== null, 403);

        try {
            $driveStatus = array_merge([
                'available' => true,
                'connected' => false,
                'email' => '',
                'folder_name' => '',
                'last_checked_at' => '',
            ], $drive->status());
        } catch (Throwable $exception) {
            report($exception);
            $driveStatus = [
                'available' => false,
                'connected' => false,
                'email' => '',
                'folder_name' => '',
                'last_checked_at' => '',
            ];
        }

        return view('Invoices::pages.invoices.dashboard', [
            'dashboard' => $dashboard->forUser($admin),
            'driveStatus' => $driveStatus,
        ]);
    }
}

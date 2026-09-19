<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Invoices\Services\InvoiceDashboardService;
use Modules\Invoices\Services\InvoicePartnerCandidateService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;

final class InvoicesDashboardController extends Controller
{
    public function __invoke(Request $request, InvoiceDashboardService $dashboard, InvoicePartnerCandidateService $partners, GoogleDriveConnectionService $drive): View
    {
        $admin = auth('admin')->user();
        $classificationYear = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));

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
            'dashboard' => $dashboard->forUser($admin, $classificationYear),
            'classificationYear' => $classificationYear,
            'driveStatus' => $driveStatus,
            'partnerSync' => $partners->summary(),
        ]);
    }
}

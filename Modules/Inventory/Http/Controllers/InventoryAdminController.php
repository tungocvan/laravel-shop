<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryDashboardService;

final class InventoryAdminController extends Controller
{
    public function dashboard(InventoryDashboardService $dashboard): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin !== null, 403);

        return view('Inventory::pages.dashboard', [
            'dashboard' => $dashboard->snapshot(),
        ]);
    }

    public function invoiceInbox(): View
    {
        return view('Inventory::pages.invoice-inbox');
    }

    public function intake(): View
    {
        return view('Inventory::pages.intake');
    }

    public function workspace(Request $request): View
    {
        $workspace = (string) $request->route('workspace');
        abort_unless(in_array($workspace, [
            'warehouses', 'items', 'receipts', 'issues', 'transfers',
            'stocktakes', 'stock', 'lots', 'movements',
        ], true), 404);

        return view('Inventory::pages.workspace', ['workspace' => $workspace]);
    }
}

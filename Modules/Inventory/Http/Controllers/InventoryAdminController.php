<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Services\InventoryDashboardService;
use Modules\Invoices\Integrations\Inventory\InvoiceInventoryHandoffService;
use Throwable;

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

    public function invoiceInbox(Request $request): View
    {
        $selectedInbox = $request->filled('inbox')
            ? InvoiceInbox::query()->with('receipt')->find((int) $request->query('inbox'))
            : null;

        return view('Inventory::pages.invoice-inbox', [
            'selectedInboxHeader' => $selectedInbox,
        ]);
    }

    public function refreshInvoiceInboxSource(int $inboxId): RedirectResponse
    {
        $admin = auth('admin')->user();
        abort_unless((bool) $admin?->can('inventory.receipt.manage'), 403);

        $inbox = InvoiceInbox::query()->with('receipt')->findOrFail($inboxId);
        $redirect = fn (InvoiceInbox $target): RedirectResponse => redirect()->route('admin.inventory.invoice-inbox', [
            'inbox' => $target->id,
        ]);

        if ($inbox->source_invoice_id === null) {
            return $redirect($inbox)->with('inventory_error', 'Inbox không còn liên kết với hóa đơn nguồn để cập nhật lại.');
        }

        if ($inbox->receipt?->status === 'CONFIRMED') {
            return $redirect($inbox)->with('inventory_error', 'Phiếu nhập đã xác nhận nên dữ liệu nguồn đã bị khóa để bảo đảm lịch sử tồn kho.');
        }

        try {
            $refreshed = app(InvoiceInventoryHandoffService::class)->handoff((int) $inbox->source_invoice_id);
        } catch (Throwable $exception) {
            report($exception);

            return $redirect($inbox)->with('inventory_error', $exception->getMessage());
        }

        $message = $refreshed->receipt?->status === 'DRAFT'
            ? 'Đã cập nhật lại dữ liệu từ hóa đơn nguồn. Phiếu nhập đang ở DRAFT; hãy kiểm tra và bấm “Cập nhật phiếu nhập nháp” để làm mới nội dung phiếu.'
            : 'Đã cập nhật lại dữ liệu từ hóa đơn nguồn. Đối chiếu mặt hàng hiện có được giữ lại khi còn hợp lệ.';

        return $redirect($refreshed)->with('inventory_success', $message);
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

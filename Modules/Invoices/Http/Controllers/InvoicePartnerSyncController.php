<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Invoices\Services\InvoicePartnerCandidateService;
use Throwable;

final class InvoicePartnerSyncController extends Controller
{
    public function __invoke(InvoicePartnerCandidateService $partners): RedirectResponse
    {
        try {
            $summary = $partners->sync();

            return redirect()
                ->route('admin.invoices.dashboard')
                ->with('partner_sync_summary', $summary);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.invoices.dashboard')
                ->with('partner_sync_error', 'Không thể chuẩn bị dữ liệu đồng bộ Partner. Vui lòng kiểm tra module Partner và thử lại.');
        }
    }
}

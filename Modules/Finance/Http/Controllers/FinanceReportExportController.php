<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Finance\Exports\FinanceVoucherRegisterExport;
use Modules\Finance\Services\FinanceReportService;

final class FinanceReportExportController
{
    public function __invoke(Request $request)
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.export'), 403);
        $filters = app(FinanceReportService::class)->validateFilters($request->only([
            'from', 'to', 'direction', 'account_id', 'origin', 'category', 'counterparty', 'ids',
        ]));

        return Excel::download(new FinanceVoucherRegisterExport($filters), 'so-chung-tu-thu-chi-'.now()->format('Ymd-His').'.xlsx');
    }
}

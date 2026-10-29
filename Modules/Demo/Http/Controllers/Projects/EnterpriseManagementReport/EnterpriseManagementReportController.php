<?php

namespace Modules\Demo\Http\Controllers\Projects\EnterpriseManagementReport;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class EnterpriseManagementReportController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.enterprise-management-report.index');
    }
}

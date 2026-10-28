<?php

namespace App\Http\Controllers\Demo;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class EnterpriseManagementReportController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.bao-cao-quan-tri-doanh-nghiep.index');
    }
}

<?php
namespace Modules\Demo\Http\Controllers\Projects\DailyWorkReport;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class DailyWorkReportController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.daily-work-report.index');
    }
}

<?php
namespace App\Http\Controllers\Demo;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
final class DailyWorkReportController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.bao-cao-cong-viec-hang-ngay.index');
    }
}

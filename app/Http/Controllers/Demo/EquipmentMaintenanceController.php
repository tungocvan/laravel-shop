<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class EquipmentMaintenanceController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.quan-ly-thiet-bi-bao-tri.index');
    }
}

<?php

namespace Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class EquipmentMaintenanceController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.equipment-maintenance.index');
    }
}

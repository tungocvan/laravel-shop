<?php

namespace Modules\Demo\Http\Controllers\Projects\WarehouseInventory;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

class WarehouseInventoryController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.warehouse-inventory.index');
    }
}

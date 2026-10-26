<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WarehouseInventoryController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.warehouse-inventory.index');
    }
}

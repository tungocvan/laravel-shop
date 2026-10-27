<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DebtManagementController extends Controller
{
    public function __invoke(): View
    {
        return view('demo.debt-management.index');
    }
}

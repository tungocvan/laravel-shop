<?php

namespace Modules\Demo\Http\Controllers\Projects\DebtManagement;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DebtManagementController extends Controller
{
    public function __invoke(): View
    {
        return view('demo::projects.debt-management.index');
    }
}

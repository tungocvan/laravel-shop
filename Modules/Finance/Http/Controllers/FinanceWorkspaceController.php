<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class FinanceWorkspaceController extends Controller
{
    public function __invoke(): View
    {
        return view('Finance::workspace');
    }
}

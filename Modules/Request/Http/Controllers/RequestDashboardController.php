<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Request\Application\Queries\RequestDashboardQuery;

final class RequestDashboardController extends Controller
{
    public function __invoke(RequestDashboardQuery $query): View
    {
        $user = auth('admin')->user();

        return view('Request::dashboard', [
            'dashboard' => $query->forUser($user),
        ]);
    }
}

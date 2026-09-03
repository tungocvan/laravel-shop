<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\System\Services\AdminLoginRedirectService;

class DashboardController extends Controller
{
    public function entry(AdminLoginRedirectService $redirect): RedirectResponse
    {
        return redirect()->route($redirect->configuredRoute());
    }

    public function index(): View
    {
        return view('Admin::pages.dashboard');
    }
}

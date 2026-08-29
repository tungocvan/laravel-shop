<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @deprecated Use the System settings routes directly.
 */
class SettingController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.system.settings.index');
    }

    public function profile(): RedirectResponse
    {
        return redirect()->route('admin.profile');
    }

    public function modules(): RedirectResponse
    {
        return redirect()->route('admin.system.modules');
    }
}

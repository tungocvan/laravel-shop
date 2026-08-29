<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @deprecated Use the System environment-settings route directly.
 */
class EnvConfigController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.system.settings.env');
    }
}

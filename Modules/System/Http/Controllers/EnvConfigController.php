<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnvConfigController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can('system.env.view'), 403);

        $allowed = ['database', 'mail', 'advanced', 'payment', 'storage', 'social'];
        $requested = (string) $request->query('tab', 'database');
        $tab = in_array($requested, $allowed, true) ? $requested : 'database';

        return redirect()->route('admin.system.index', ['tab' => 'env-'.$tab]);
    }
}

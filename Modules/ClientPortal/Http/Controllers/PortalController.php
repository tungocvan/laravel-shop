<?php

namespace Modules\ClientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

class PortalController extends Controller
{
    public function login(Request $request, ClientPortalSettingsService $settings): View|RedirectResponse
    {
        if ($request->user('web')) {
            return redirect()->route('client.apps.index');
        }

        return view('ClientPortal::pages.login', [
            'pwaGeneral' => $settings->pwaGeneral(),
            'pwaLogin' => $settings->pwaLogin(),
        ]);
    }

    public function index(Request $request, ApplicationRegistry $registry): View
    {
        return view('ClientPortal::pages.apps', [
            'applications' => $registry->forUser($request->user('web')),
        ]);
    }
}

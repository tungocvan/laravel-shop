<?php

namespace Modules\ClientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;

class PortalController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry): View
    {
        return view('ClientPortal::pages.apps', [
            'applications' => $registry->forUser($request->user('web')),
        ]);
    }
}

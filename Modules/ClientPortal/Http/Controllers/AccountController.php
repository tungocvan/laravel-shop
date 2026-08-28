<?php

namespace Modules\ClientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\PortalAccountPresenter;

class AccountController extends Controller
{
    public function show(Request $request, PortalAccountPresenter $accounts): View
    {
        return view('ClientPortal::pages.account', [
            'account' => $accounts->for($request->user('web')),
        ]);
    }

    public function settings(Request $request, PortalAccountPresenter $accounts): View
    {
        return view('ClientPortal::pages.settings', [
            'account' => $accounts->for($request->user('web')),
        ]);
    }
}

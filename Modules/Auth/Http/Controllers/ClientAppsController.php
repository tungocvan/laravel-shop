<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ClientApplicationRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientAppsController extends Controller
{
    public function index(Request $request, ClientApplicationRegistry $registry): View
    {
        return view('Auth::pages.client.apps', [
            'applications' => $registry->forUser($request->user('web')),
        ]);
    }
}

<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class RequestRequesterController extends Controller
{
    public function catalog(): View
    {
        return view('Request::requester.catalog');
    }

    public function create(string $typePublicId): View
    {
        return view('Request::requester.create', compact('typePublicId'));
    }

    public function mine(): View
    {
        return view('Request::requester.mine');
    }

    public function show(string $requestPublicId): View
    {
        return view('Request::requester.show', compact('requestPublicId'));
    }
}

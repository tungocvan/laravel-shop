<?php

namespace Modules\Partner\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PartnerController extends Controller
{
    public function index(): View
    {
        return view('Partner::pages.index');
    }

    public function create(): View
    {
        return view('Partner::pages.create');
    }

    public function edit(int $id): View
    {
        return view('Partner::pages.edit', [
            'id' => $id,
        ]);
    }
}

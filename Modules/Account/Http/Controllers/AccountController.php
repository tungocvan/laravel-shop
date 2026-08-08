<?php

namespace Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('Account::pages.index');
    }

    public function create(): View
    {
        return view('Account::pages.create');
    }

    public function edit(int $id): View
    {
        return view('Account::pages.edit', [
            'id' => $id,
        ]);
    }
}

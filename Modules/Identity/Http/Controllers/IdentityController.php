<?php

namespace Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Identity\Models\User;

class IdentityController extends Controller
{
    public function index(): View
    {
        return view('Identity::pages.index');
    }

    public function create(): View
    {
        return view('Identity::pages.create');
    }

    public function edit(User $identity): View
    {
        return view('Identity::pages.edit', [
            'identity' => $identity,
        ]);
    }
}

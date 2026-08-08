<?php

namespace Modules\Facebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FacebookPostController extends Controller
{
    public function index(): View
    {
        return view('Facebook::pages.posts.index');
    }

    public function create(): View
    {
        return view('Facebook::pages.posts.create');
    }

    public function edit(int $id): View
    {
        return view('Facebook::pages.posts.edit', ['id' => $id]);
    }

    public function show(int $id): View
    {
        return view('Facebook::pages.posts.show', ['id' => $id]);
    }
}

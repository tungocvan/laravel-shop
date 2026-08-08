<?php

namespace Modules\Category\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('Category::pages.categories.index');
    }

    public function create(): View
    {
        return view('Category::pages.categories.create');
    }

    public function edit(int $id): View
    {
        return view('Category::pages.categories.edit', compact('id'));
    }
}

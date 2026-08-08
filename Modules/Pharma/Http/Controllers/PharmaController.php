<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
//use Illuminate\Http\Request;

class PharmaController extends Controller
{
    public function __construct()
    {
       // $this->middleware('permission:pharma-list|pharma-create|pharma-edit|pharma-delete', ['only' => ['index','show']]);
       // $this->middleware('permission:pharma-create', ['only' => ['create','store']]);
       // $this->middleware('permission:pharma-edit', ['only' => ['edit','update']]);
       // $this->middleware('permission:pharma-delete', ['only' => ['destroy']]);
    }

    public function index(): View
    {
        return view('Pharma::pages.index');
    }

    public function create(): View
    {
        return view('Pharma::pages.create');
    }

    public function edit(int $id): View
    {
        return view('Pharma::pages.edit', compact('id'));
    }
}

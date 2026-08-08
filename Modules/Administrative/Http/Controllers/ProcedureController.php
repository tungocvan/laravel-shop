<?php

namespace Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Administrative\Services\ProcedureService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcedureController extends Controller
{
    public function index(): View
    {
        return view('Administrative::pages.procedures.index');
    }

    public function create(): View
    {
        return view('Administrative::pages.procedures.create');
    }

    public function edit(int $id): View
    {
        return view('Administrative::pages.procedures.edit', compact('id'));
    }

    public function downloadTemplate(int $id, ProcedureService $service): StreamedResponse
    {
        return $service->downloadTemplate($id);
    }
}

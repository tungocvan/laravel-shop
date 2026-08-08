<?php

namespace Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Administrative\Services\AdministrativeFileService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(): View
    {
        return view('Administrative::pages.submissions.index');
    }

    public function show(int $id): View
    {
        return view('Administrative::pages.submissions.show', compact('id'));
    }

    public function downloadFile(int $submission, int $file, AdministrativeFileService $service): StreamedResponse
    {
        return $service->downloadForAdmin($submission, $file);
    }
}

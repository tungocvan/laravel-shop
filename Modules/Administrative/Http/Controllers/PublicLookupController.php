<?php

namespace Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Administrative\Services\LookupService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicLookupController extends Controller
{
    public function index(): View
    {
        return view('Administrative::pages.public.lookup');
    }

    public function show(string $accessToken, LookupService $service): View
    {
        return view('Administrative::pages.public.lookup-result', [
            'submission' => $service->submissionForAccess($accessToken),
            'accessToken' => $accessToken,
        ]);
    }

    public function downloadResult(string $accessToken, int $file, LookupService $service): StreamedResponse
    {
        return $service->downloadResult($accessToken, $file);
    }
}

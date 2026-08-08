<?php

namespace Modules\Administrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Services\ProcedureService;
use Modules\Administrative\Services\ReceiptService;
use Modules\Administrative\Services\SubmissionService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicProcedureController extends Controller
{
    public function index(ProcedureService $service): View
    {
        return view('Administrative::pages.public.index', [
            'procedures' => $service->listActiveForPublic(),
        ]);
    }

    public function show(AdministrativeProcedure $procedure): View
    {
        $this->ensureActive($procedure);

        return view('Administrative::pages.public.show', compact('procedure'));
    }

    public function submit(AdministrativeProcedure $procedure): View
    {
        $this->ensureActive($procedure);

        return view('Administrative::pages.public.submit', compact('procedure'));
    }

    public function success(string $receipt, SubmissionService $service): View
    {
        $submissionId = session("administrative.receipts.{$receipt}");
        abort_unless(is_numeric($submissionId), 404);

        $submission = $service->findForReceipt((int) $submissionId);

        return view('Administrative::pages.public.success', [
            'submission' => $submission,
            'lookupToken' => session("administrative.lookup_tokens.{$receipt}"),
        ]);
    }

    public function downloadTemplate(AdministrativeProcedure $procedure, ProcedureService $service): StreamedResponse
    {
        return $service->downloadPublicTemplate($procedure);
    }

    public function downloadReceipt(string $receipt, ReceiptService $service): Response
    {
        return $service->downloadFromSession($receipt);
    }

    private function ensureActive(AdministrativeProcedure $procedure): void
    {
        abort_unless($procedure->is_active && ! $procedure->trashed(), 404);
    }
}

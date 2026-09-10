<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\InvoiceFileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoicesController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.invoices.hoadon-list');
    }

    public function hoadon(GdtApiService $gdtApiService): View
    {
        return view('Invoices::pages.invoices.sync', [
            'gdtReady' => $gdtApiService->hasToken(),
        ]);
    }

    public function hoadonList(): View
    {
        return view('Invoices::pages.invoices.index');
    }

    public function sourceData(): View
    {
        return view('Invoices::pages.invoices.source-data');
    }

    public function partnerReport(): View
    {
        return view('Invoices::pages.invoices.partner-report');
    }

    public function backupRestore(): View
    {
        return view('Invoices::pages.invoices.backup-restore');
    }

    public function createToken(): View
    {
        return view('Invoices::pages.invoices.authenticate');
    }

    public function downloadInvoice(Invoices $invoice, InvoiceFileService $service): BinaryFileResponse
    {
        try {
            $filePath = $service->pdfPathForInvoice($invoice);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->download($filePath, $service->filenameForInvoice($invoice));
    }

    public function download(string $lookup_code, InvoiceFileService $service): BinaryFileResponse
    {
        try {
            $filePath = $service->pdfPath($lookup_code);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->download($filePath, "{$lookup_code}.pdf");
    }
}

<?php

namespace Modules\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Invoices\Services\InvoiceFileService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('admin.invoices.hoadon-list');
    }

    public function hoadon(): View
    {
        return view('Invoices::pages.invoices.sync');
    }

    public function hoadonList(): View
    {
        return view('Invoices::pages.invoices.index');
    }

    public function createToken(): View
    {
        return view('Invoices::pages.invoices.authenticate');
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

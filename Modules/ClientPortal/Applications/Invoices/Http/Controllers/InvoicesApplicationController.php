<?php

namespace Modules\ClientPortal\Applications\Invoices\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ClientPortal\Applications\Invoices\Services\ClientInvoiceWorkspaceService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Invoices\Exports\InvoicesSelectedExport;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtSyncReadinessService;
use Modules\Invoices\Services\InvoiceFileService;
use Modules\Invoices\Services\InvoicePdfService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class InvoicesApplicationController extends Controller
{
    public function dashboard(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, ClientInvoiceWorkspaceService $workspace): View
    {
        return $this->applicationView('dashboard', $registry, $settings, $workspace->dashboardData($request));
    }

    public function partners(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, ClientInvoiceWorkspaceService $workspace): View
    {
        return $this->applicationView('partners', $registry, $settings, $workspace->partnerReportData($request));
    }

    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, ClientInvoiceWorkspaceService $workspace): View
    {
        $request->session()->put('client.invoices.return_to', $request->fullUrl());

        return $this->applicationView('index', $registry, $settings, $workspace->listData($request));
    }

    public function show(Request $request, Invoices $invoice, ApplicationRegistry $registry, ClientPortalSettingsService $settings, InvoicePdfService $pdf): View
    {
        $this->authorizePermission($request, $registry, 'client.invoices.detail.view');

        return $this->applicationView('show', $registry, $settings, [
            'invoice' => $invoice,
            'pdfStatus' => $pdf->statusForInvoice($invoice),
            'returnTo' => $request->session()->get('client.invoices.return_to', route('client.invoices.index')),
        ]);
    }

    public function export(Request $request, ApplicationRegistry $registry, ClientInvoiceWorkspaceService $workspace): BinaryFileResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.export');
        $records = $workspace->exportRecords($request);
        $suffix = $request->input('selected', []) === [] ? 'loc' : 'chon';

        return Excel::download(new InvoicesSelectedExport($records), 'hoa-don-'.$suffix.'-'.now()->format('Ymd-His').'.xlsx');
    }

    public function pdf(Request $request, Invoices $invoice, ApplicationRegistry $registry, InvoiceFileService $files): BinaryFileResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.pdf.download');

        try {
            $path = $files->pdfPathForInvoice($invoice);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->download($path, $files->filenameForInvoice($invoice));
    }

    public function sync(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, ClientInvoiceWorkspaceService $workspace, GdtSyncReadinessService $readiness): View
    {
        return $this->applicationView('sync', $registry, $settings, [
            'syncId' => $request->query('sync_id'),
            'syncStatus' => $workspace->syncStatus($request->query('sync_id')),
            'syncReadiness' => $readiness->readiness(),
            'captchaSvg' => $request->session()->get('client.invoices.gdt.captcha_svg'),
        ]);
    }

    public function refreshSyncCaptcha(Request $request, ApplicationRegistry $registry, GdtSyncReadinessService $readiness): RedirectResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.sync');

        if ($readiness->hasToken()) {
            return redirect()->route('client.invoices.sync')->with('status', 'Phiên GDT vẫn đang sẵn sàng.');
        }

        $captcha = $readiness->loadCaptcha();
        if (! isset($captcha['key'], $captcha['content'])) {
            return redirect()->route('client.invoices.sync')->with('error', 'Không thể tải CAPTCHA từ GDT. Vui lòng thử lại.');
        }

        $request->session()->put('client.invoices.gdt.captcha_key', $captcha['key']);
        $request->session()->put('client.invoices.gdt.captcha_svg', $captcha['content']);

        return redirect()->route('client.invoices.sync');
    }

    public function authenticateSync(Request $request, ApplicationRegistry $registry, GdtSyncReadinessService $readiness): RedirectResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.sync');
        $validated = $request->validate(['cvalue' => ['required', 'string', 'max:20']]);
        $ckey = $request->session()->get('client.invoices.gdt.captcha_key');

        if (! is_string($ckey) || $ckey === '') {
            return redirect()->route('client.invoices.sync')->with('error', 'CAPTCHA đã hết phiên. Vui lòng tải CAPTCHA mới.');
        }

        $result = $readiness->authenticate($validated['cvalue'], $ckey);
        if (($result['status'] ?? 'error') !== 'success') {
            $request->session()->forget(['client.invoices.gdt.captcha_key', 'client.invoices.gdt.captcha_svg']);

            return redirect()->route('client.invoices.sync')->with('error', $result['message'] ?? 'Xác thực GDT không thành công. Vui lòng tải CAPTCHA mới.');
        }

        $request->session()->forget(['client.invoices.gdt.captcha_key', 'client.invoices.gdt.captcha_svg']);

        return redirect()->route('client.invoices.sync')->with('status', 'Đã kết nối GDT. Bạn có thể bắt đầu đồng bộ.');
    }

    public function startSync(Request $request, ApplicationRegistry $registry, ClientInvoiceWorkspaceService $workspace, GdtSyncReadinessService $readiness): RedirectResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.sync');

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'direction' => ['required', 'in:sold,purchase'],
        ]);

        if (! $readiness->hasToken()) {
            return redirect()->route('client.invoices.sync')->withInput()->with('error', 'Phiên GDT đã hết hạn. Vui lòng kết nối GDT bằng CAPTCHA trước khi đồng bộ.');
        }

        $syncId = $workspace->dispatchSync($validated['start'], $validated['end'], $validated['direction']);

        return redirect()->route('client.invoices.sync', ['sync_id' => $syncId])
            ->with('status', 'Đã xếp hàng tác vụ đồng bộ. Bạn có thể rời màn hình này và quay lại sau.');
    }

    private function applicationView(string $view, ApplicationRegistry $registry, ClientPortalSettingsService $settings, array $data = []): View
    {
        $application = $registry->find('invoices');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.invoices.'.$view, array_merge([
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
        ], $data));
    }

    private function authorizePermission(Request $request, ApplicationRegistry $registry, string $permission): void
    {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, $permission), 403);
    }
}

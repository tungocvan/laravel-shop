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
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\GdtCompletedExport;
use Modules\Invoices\Services\GdtPosSecureContinuation;
use Illuminate\Validation\ValidationException;
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
        $application = $registry->find('invoices');
        $feature = collect($application['features'] ?? [])->firstWhere('key', 'sync');

        return $this->applicationView('sync', $registry, $settings, [
            'syncPresentation' => $feature ? $settings->featurePresentation('invoices', $feature) : [],
            'syncId' => $request->query('sync_id'),
            'syncStatus' => $workspace->syncStatus($request->query('sync_id')),
            'syncReadiness' => $readiness->readiness(),
            'captchaSvg' => $request->session()->get('client.invoices.gdt.captcha_svg'),
            'posCaptchaImage' => $request->session()->get('client.invoices.pos.image'),
            'posFilters' => $request->session()->get('client.invoices.pos.filters'),
            'posPreview' => $request->session()->get('client.invoices.pos.preview'),
            'posPending' => $request->session()->get('client.invoices.pos.pending'),
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
            'source' => ['sometimes', 'in:regular'],
        ]);

        if (! $readiness->hasToken()) {
            return redirect()->route('client.invoices.sync')->withInput()->with('error', 'Phiên GDT đã hết hạn. Vui lòng kết nối GDT bằng CAPTCHA trước khi đồng bộ.');
        }

        $syncId = $workspace->dispatchSync($validated['start'], $validated['end'], $validated['direction']);

        return redirect()->route('client.invoices.sync', ['sync_id' => $syncId])
            ->with('status', 'Đã xếp hàng tác vụ đồng bộ. Bạn có thể rời màn hình này và quay lại sau.');
    }

    private function posFilters(Request $request): array
    {
        $filters = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'direction' => ['required', 'in:sold,purchase'],
        ]);
        if (substr($filters['start'], 0, 7) !== substr($filters['end'], 0, 7)) {
            throw ValidationException::withMessages(['end' => 'POS chỉ hỗ trợ một tháng dương lịch mỗi lần.']);
        }
        return $filters;
    }

    private function existingPosFile(array $filters): ?string
    {
        $path = app(GdtCompletedExport::class)->path($filters['start'], $filters['end'], $filters['direction'] === 'purchase', 'pos');

        return is_file($path) && is_readable($path) && filesize($path) > 0 ? $path : null;
    }

    private function posFingerprint(array $filters): string
    {
        return hash('sha256', implode('|', [$filters['start'], $filters['end'], $filters['direction']]));
    }

    public function refreshPosCaptcha(Request $request, ApplicationRegistry $registry, GdtApiService $api, GdtSyncReadinessService $readiness): RedirectResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.sync');
        $filters = $this->posFilters($request);
        $purpose = $request->validate(['purpose' => ['required', 'in:preview,sync,preview_page2,sync_page2']])['purpose'];
        $fingerprint = $this->posFingerprint($filters);
        if ($purpose === 'preview' && $this->existingPosFile($filters) !== null && ! $request->boolean('force_refresh')) {
            return back()->withInput()->with('error', 'File Excel POS đã tồn tại: '.basename($this->existingPosFile($filters)).'. Chỉ tra cứu lại GDT khi bạn xác nhận.');
        }
        if (in_array($purpose, ['preview_page2', 'sync_page2'], true)
            && ($request->session()->get('client.invoices.pos.pending.fingerprint') !== $fingerprint
                || $request->session()->get('client.invoices.pos.pending.purpose') !== ($purpose === 'preview_page2' ? 'preview' : 'sync'))) {
            return back()->withInput()->with('error', 'Checkpoint POS không hợp lệ hoặc bộ lọc đã thay đổi. Vui lòng bắt đầu lại.');
        }
        if ($purpose === 'sync' && ($request->session()->get('client.invoices.pos.preview.fingerprint') !== $fingerprint
            || (int) $request->session()->get('client.invoices.pos.preview.count', 0) <= 0)) {
            return back()->withInput()->with('error', 'Hãy xem trước hóa đơn POS với bộ lọc hiện tại trước khi đồng bộ.');
        }
        if (! $readiness->hasToken()) {
            return back()->withInput()->with('error', 'Vui lòng kết nối GDT trước khi tra cứu POS.');
        }
        if (in_array($purpose, ['preview', 'sync'], true)) {
            $pending = $request->session()->pull('client.invoices.pos.pending');
            if (is_array($pending) && is_string($pending['id'] ?? null)) {
                app(GdtPosSecureContinuation::class)->discard((int) $request->user('web')->getAuthIdentifier(), $request->session()->getId(), $pending['id']);
            }
        }
        $request->session()->forget(['client.invoices.pos.image', 'client.invoices.pos.filters']);
        try {
            $challenge = $api->loadPosCaptcha('web-'.$request->user('web')->getAuthIdentifier().'-'.$request->session()->getId());
            $request->session()->put('client.invoices.pos.image', $challenge['image']);
            $request->session()->put('client.invoices.pos.filters', $filters + ['purpose' => $purpose]);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'Không tải được CAPTCHA POS. Vui lòng thử lại.');
        }
        return redirect()->route('client.invoices.sync');
    }

    public function submitPosCaptcha(Request $request, ApplicationRegistry $registry, GdtApiService $api, ClientInvoiceWorkspaceService $workspace, GdtInvoiceService $gdt): RedirectResponse
    {
        $this->authorizePermission($request, $registry, 'client.invoices.sync');
        $validated = $request->validate(['cvalue' => ['required', 'string', 'max:20']]);
        $filters = $request->session()->pull('client.invoices.pos.filters');
        $request->session()->forget('client.invoices.pos.image');
        if (! is_array($filters) || ! in_array($filters['purpose'] ?? null, ['preview', 'sync', 'preview_page2', 'sync_page2'], true)) {
            return redirect()->route('client.invoices.sync')->with('error', 'CAPTCHA POS đã hết phiên. Vui lòng tải mã mới.');
        }
        try {
            $challenge = $api->consumePosCaptcha('web-'.$request->user('web')->getAuthIdentifier().'-'.$request->session()->getId(), $validated['cvalue']);
            $fingerprint = $this->posFingerprint($filters);
            $vatIn = $filters['direction'] === 'purchase';
            if (in_array($filters['purpose'], ['preview_page2', 'sync_page2'], true)) {
                $pending = $request->session()->pull('client.invoices.pos.pending');
                $expected = $filters['purpose'] === 'preview_page2' ? 'preview' : 'sync';
                if (! is_array($pending) || ($pending['fingerprint'] ?? null) !== $fingerprint
                    || ($pending['purpose'] ?? null) !== $expected || ! is_string($pending['id'] ?? null)) {
                    throw new \RuntimeException('Checkpoint POS hết hạn hoặc không hợp lệ.');
                }
                $checkpoint = app(GdtPosSecureContinuation::class)->consume(
                    (int) $request->user('web')->getAuthIdentifier(), $request->session()->getId(), $fingerprint, $pending['id']
                );
                if ($checkpoint === null) {
                    throw new \RuntimeException('Checkpoint POS đã hết hạn.');
                }
                if ($expected === 'preview') {
                    $preview = $gdt->resumePosPreview($filters['start'], $filters['end'], $vatIn, $challenge, $checkpoint);
                    $request->session()->put('client.invoices.pos.preview', [
                        'fingerprint' => $fingerprint, 'count' => $preview['count'],
                        'filters' => array_intersect_key($filters, array_flip(['start', 'end', 'direction'])),
                    ]);
                    return redirect()->route('client.invoices.sync')->with('status', 'Đã xem trước đủ '.number_format($preview['count']).' hóa đơn POS; chưa ghi dữ liệu.');
                }
                $rows = $gdt->resumePosCanonicalRows($filters['start'], $filters['end'], $vatIn, $challenge, $checkpoint);
                $result = $workspace->syncVerifiedPos($filters['start'], $filters['end'], $filters['direction'], $rows, $checkpoint['total']);
                return redirect()->route('client.invoices.sync', ['sync_id' => $result['sync_id']])
                    ->with(($result['status']['state'] ?? '') === 'completed' ? 'status' : 'error', $result['status']['message']);
            }
            if ($filters['purpose'] === 'preview') {
                $request->session()->forget('client.invoices.pos.preview');
                $preview = $gdt->previewPosFirstPage($filters['start'], $filters['end'], $vatIn, $challenge);
                if ($preview['incomplete']) {
                    $snapshot = $gdt->takePosFirstPage();
                    if (! is_array($snapshot)) {
                        throw new \RuntimeException('Không tạo được checkpoint POS.');
                    }
                    $id = app(GdtPosSecureContinuation::class)->store(
                        (int) $request->user('web')->getAuthIdentifier(), $request->session()->getId(), $fingerprint,
                        $snapshot['first_page'], $snapshot['cursor'], $snapshot['total'], $snapshot['cookies'] ?? []
                    );
                    $request->session()->put('client.invoices.pos.pending', [
                        'id' => $id, 'fingerprint' => $fingerprint, 'purpose' => 'preview',
                        'filters' => array_intersect_key($filters, array_flip(['start', 'end', 'direction'])),
                        'total' => $preview['total'],
                    ]);
                    return redirect()->route('client.invoices.sync')->with('status', 'Đã đọc trang 1; cần CAPTCHA trang 2 để xác minh đủ '.number_format($preview['total']).' hóa đơn.');
                }
                $request->session()->put('client.invoices.pos.preview', [
                    'fingerprint' => $fingerprint, 'count' => (int) $preview['count'],
                    'filters' => array_intersect_key($filters, array_flip(['start', 'end', 'direction'])),
                ]);
                return redirect()->route('client.invoices.sync')->with('status', 'Tra cứu POS hoàn tất: '.number_format($preview['count']).' hóa đơn. Chưa ghi dữ liệu.');
            }
            $preview = $request->session()->get('client.invoices.pos.preview');
            if (! is_array($preview) || ($preview['fingerprint'] ?? null) !== $fingerprint
                || (int) ($preview['count'] ?? 0) <= 0) {
                return redirect()->route('client.invoices.sync')->with('error', 'Kết quả xem trước không còn hợp lệ. Vui lòng tra cứu lại.');
            }
            $first = $gdt->previewPosFirstPage($filters['start'], $filters['end'], $vatIn, $challenge);
            if ($first['incomplete']) {
                $snapshot = $gdt->takePosFirstPage();
                if (! is_array($snapshot)) {
                    throw new \RuntimeException('Không tạo được checkpoint đồng bộ POS.');
                }
                $id = app(GdtPosSecureContinuation::class)->store(
                    (int) $request->user('web')->getAuthIdentifier(), $request->session()->getId(), $fingerprint,
                    $snapshot['first_page'], $snapshot['cursor'], $snapshot['total'], $snapshot['cookies'] ?? []
                );
                $request->session()->put('client.invoices.pos.pending', [
                    'id' => $id, 'fingerprint' => $fingerprint, 'purpose' => 'sync',
                    'filters' => array_intersect_key($filters, array_flip(['start', 'end', 'direction'])),
                    'total' => $first['total'],
                ]);
                return redirect()->route('client.invoices.sync')->with('status', 'Đã xác minh trang 1; cần CAPTCHA trang 2 trước khi ghi canonical.');
            }
            // Reuse the verified first-page rows: the CAPTCHA has already been consumed.
            $rows = $gdt->takePosCompleteFirstPageRows();
            if (! is_array($rows) || count($rows) !== $first['count']) {
                throw new \RuntimeException('Không xác minh được toàn bộ hóa đơn POS trang 1.');
            }
            $result = $workspace->syncVerifiedPos($filters['start'], $filters['end'], $filters['direction'], $rows, $first['count']);
            return redirect()->route('client.invoices.sync', ['sync_id' => $result['sync_id']])
                ->with(($result['status']['state'] ?? '') === 'completed' ? 'status' : 'error', $result['status']['message']);
        } catch (\Throwable $exception) {
            report($exception);
            $request->session()->forget('client.invoices.pos.preview');
            return redirect()->route('client.invoices.sync')->with('error', 'Yêu cầu POS thất bại hoặc CAPTCHA hết hiệu lực. Vui lòng tải CAPTCHA mới.');
        }
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

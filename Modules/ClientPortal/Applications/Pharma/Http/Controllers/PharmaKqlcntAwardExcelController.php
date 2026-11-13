<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntAwardVisibility;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Muasamcong\Exports\KqlcntAwardImportTemplateExport;
use Modules\Muasamcong\Exports\KqlcntAwardWorkbookExport;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Services\KqlcntAwardExcelImportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PharmaKqlcntAwardExcelController extends Controller
{
    private function authorizeAction(Request $request, ApplicationRegistry $registry, string $action): void
    {
        abort_unless($request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.kqlcnt-awards.'.$action), 403);
    }

    public function template(Request $request, ApplicationRegistry $registry): BinaryFileResponse
    {
        $this->authorizeAction($request, $registry, 'import');
        return Excel::download(new KqlcntAwardImportTemplateExport(), 'KQLCNT-Mau-Import.xlsx');
    }

    public function preview(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, KqlcntAwardExcelImportService $service)
    {
        $this->authorizeAction($request, $registry, 'import');
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);
        $preview = $service->preview($request->file('file'), 'web');
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        return view('ClientPortal::applications.pharma.kqlcnt-award-import-preview', [
            'preview' => $preview,
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
        ]);
    }

    public function confirm(Request $request, ApplicationRegistry $registry, KqlcntAwardExcelImportService $service)
    {
        $this->authorizeAction($request, $registry, 'import');
        $request->validate(['token' => ['required', 'string', 'size:48']]);
        $result = $service->confirm($request->string('token')->toString(), false, 'web');
        return redirect()->route('client.pharma.kqlcnt-awards.index')
            ->with('success', 'Đã import: '.$result['updated'].' cập nhật, '.$result['created'].' thêm mới, '.$result['skipped'].' bỏ qua.');
    }

    public function export(Request $request, ApplicationRegistry $registry, KqlcntAwardVisibility $visibility): BinaryFileResponse
    {
        $this->authorizeAction($request, $registry, 'export');
        $data = $request->validate([
            'notify_no' => ['required', 'string', 'max:255'],
            'contractor_code' => ['required', 'string', 'max:255'],
        ]);
        $visibility->authorizeExport($request, $data['notify_no'], $data['contractor_code']);
        $items = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->where('notify_no', $data['notify_no'])
            ->where('contractor_code', $data['contractor_code'])
            ->orderBy('id')->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['notify_no' => 'Không có dữ liệu canonical phù hợp để xuất Excel.']);
        }
        $first = $items->first();
        $package = [
            ['Mã TBMT', $first->notify_no],
            ['Số quyết định KQLCNT', $first->decision_no],
            ['Ngày quyết định', optional($first->decision_date)->format('d/m/Y')],
            ['Ngày đăng KQLCNT', optional($first->published_at)->format('d/m/Y H:i')],
            ['Mã chủ đầu tư', $first->investor_code],
            ['Chủ đầu tư', $first->investor_name],
            ['Thời gian thực hiện gói thầu', $first->contract_period_text],
            ['Thời hạn hiệu lực', $first->effect_frame_period],
            ['DANH SÁCH NHÀ THẦU', null],
            [null, null, $first->contractor_code, $first->contractor_name, $first->contract_no, $first->contract_period, $first->contract_period_unit],
        ];
        $rows = $items->map(fn (KqlcntAwardItem $item) => [
            $item->lot_no, $item->lot_name, $item->medicine_code, $item->medicine_name,
            $item->drug_group, $item->active_ingredient, $item->concentration, $item->route,
            $item->dosage_form, $item->packaging_spec, $item->shelf_life_months,
            $item->registration_or_import_license, $item->unit, $item->quantity,
            $item->price_plan, $item->winning_price, $item->amount, $item->manufacturer, $item->country,
        ])->all();
        return Excel::download(new KqlcntAwardWorkbookExport($package, KqlcntAwardImportTemplateExport::MEDICINE_HEADINGS, $rows),
            'KQLCNT-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $first->notify_no).'.xlsx');
    }
}

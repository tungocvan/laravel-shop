<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\KqlcntArraySheetExport;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KqlcntAwardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $items = $this->query($filters)->orderByDesc('synced_from_catalog_at')->orderByDesc('id')->paginate(25)->withQueryString();
        $base = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at');
        $notifyOptions = (clone $base)->whereNotNull('notify_no')->distinct()->orderBy('notify_no')->pluck('notify_no');
        $contractorOptions = (clone $base)->whereNotNull('contractor_code')->select('contractor_code', 'contractor_name')->distinct()->orderBy('contractor_name')->get();
        $medicineOptions = (clone $base)->whereNotNull('medicine_name')->distinct()->orderBy('medicine_name')->pluck('medicine_name');

        return view('Muasamcong::kqlcnt-awards.index', compact('items', 'filters', 'notifyOptions', 'contractorOptions', 'medicineOptions'));
    }

    public function edit(KqlcntAwardItem $awardItem): View
    {
        abort_if($awardItem->synced_from_catalog_at === null, 404);

        return view('Muasamcong::kqlcnt-awards.edit', compact('awardItem'));
    }

    public function update(Request $request, KqlcntAwardItem $awardItem): RedirectResponse
    {
        abort_if($awardItem->synced_from_catalog_at === null, 404);
        $data = $request->validate([
            'lot_no' => ['nullable', 'string', 'max:255'], 'lot_name' => ['nullable', 'string', 'max:500'], 'medicine_code' => ['nullable', 'string', 'max:255'],
            'medicine_name' => ['nullable', 'string', 'max:500'], 'drug_group' => ['nullable', 'string', 'max:255'], 'active_ingredient' => ['nullable', 'string', 'max:1000'],
            'concentration' => ['nullable', 'string', 'max:500'], 'route' => ['nullable', 'string', 'max:500'], 'dosage_form' => ['nullable', 'string', 'max:500'],
            'packaging_spec' => ['nullable', 'string', 'max:1000'], 'shelf_life_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'registration_or_import_license' => ['nullable', 'string', 'max:500'], 'unit' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0'], 'price_plan' => ['nullable', 'numeric', 'min:0'], 'winning_price' => ['nullable', 'numeric', 'min:0'], 'amount' => ['nullable', 'numeric', 'min:0'],
            'manufacturer' => ['nullable', 'string', 'max:1000'], 'country' => ['nullable', 'string', 'max:255'], 'decision_no' => ['nullable', 'string', 'max:500'],
            'decision_date' => ['nullable', 'date'], 'published_at' => ['nullable', 'date'], 'investor_code' => ['nullable', 'string', 'max:255'],
            'investor_name' => ['nullable', 'string', 'max:1000'], 'contract_no' => ['nullable', 'string', 'max:500'], 'is_active' => ['required', 'boolean'],
        ]);
        $awardItem->fill($data);
        $awardItem->last_verified_at = now();
        $awardItem->save();

        return redirect()->route('muasamcong.kqlcnt-awards.edit', $awardItem)->with('success', 'Đã cập nhật dữ liệu KQLCNT canonical.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);
        $ids = collect($request->input('selected_ids', []))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();
        $query = $this->query($filters)->orderByDesc('synced_from_catalog_at')->orderByDesc('id');
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }
        $rows = $query->get()->map(fn (KqlcntAwardItem $item): array => [$item->notify_no, $item->contractor_code, $item->contractor_name, $item->lot_no, $item->lot_name, $item->medicine_code, $item->medicine_name, $item->drug_group, $item->active_ingredient, $item->concentration, $item->route, $item->dosage_form, $item->packaging_spec, $item->shelf_life_months, $item->registration_or_import_license, $item->unit, $item->quantity, $item->price_plan, $item->winning_price, $item->amount, $item->manufacturer, $item->country, $item->decision_no, optional($item->decision_date)->format('d/m/Y'), optional($item->published_at)->format('d/m/Y H:i'), $item->investor_code, $item->investor_name, $item->contract_no, $item->source, $item->sync_source, $item->is_active ? 'Đang hoạt động' : 'Ngưng', optional($item->synced_from_catalog_at)->format('d/m/Y H:i:s')])->all();

        return Excel::download(new KqlcntArraySheetExport('Danh_muc_trung_thau', $this->headings(), $rows), 'KQLCNT-Awards-'.now()->format('Ymd-His').'.xlsx');
    }

    private function query(array $filters): Builder
    {
        return KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->when($filters['notify_no'], fn (Builder $q, string $v) => $q->where('notify_no', $v))
            ->when($filters['contractor_code'], fn (Builder $q, string $v) => $q->where('contractor_code', $v))
            ->when($filters['medicine_name'], fn (Builder $q, string $v) => $q->where('medicine_name', $v))
            ->when($filters['active_ingredient'], fn (Builder $q, string $v) => $q->where('active_ingredient', 'like', '%'.$v.'%'))
            ->when($filters['source'], fn (Builder $q, string $v) => $q->where('source', 'like', '%'.$v.'%'))
            ->when($filters['active'] !== '', fn (Builder $q) => $q->where('is_active', $filters['active'] === '1'))
            ->when($filters['published_from'], fn (Builder $q, string $v) => $q->whereDate('published_at', '>=', $v))->when($filters['published_to'], fn (Builder $q, string $v) => $q->whereDate('published_at', '<=', $v))
            ->when($filters['synced_from'], fn (Builder $q, string $v) => $q->whereDate('synced_from_catalog_at', '>=', $v))->when($filters['synced_to'], fn (Builder $q, string $v) => $q->whereDate('synced_from_catalog_at', '<=', $v));
    }

    private function filters(Request $request): array
    {
        $v = $request->validate(['notify_no' => ['nullable', 'string', 'max:255'], 'contractor_code' => ['nullable', 'string', 'max:255'], 'medicine_name' => ['nullable', 'string', 'max:500'], 'active_ingredient' => ['nullable', 'string', 'max:500'], 'source' => ['nullable', 'string', 'max:100'], 'active' => ['nullable', 'in:0,1'], 'published_from' => ['nullable', 'date'], 'published_to' => ['nullable', 'date'], 'synced_from' => ['nullable', 'date'], 'synced_to' => ['nullable', 'date']]);

        return collect(['notify_no', 'contractor_code', 'medicine_name', 'active_ingredient', 'source', 'active', 'published_from', 'published_to', 'synced_from', 'synced_to'])->mapWithKeys(fn ($key) => [$key => trim((string) ($v[$key] ?? ''))])->all();
    }

    private function headings(): array
    {
        return ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Quy cách', 'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK', 'ĐVT', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Mã chủ đầu tư', 'Chủ đầu tư', 'Số hợp đồng', 'Nguồn dữ liệu', 'Nguồn đồng bộ', 'Trạng thái', 'Đồng bộ lúc'];
    }
}

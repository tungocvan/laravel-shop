<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

        return view('Muasamcong::kqlcnt-awards.index', compact('items', 'filters'));
    }

    public function show(KqlcntAwardItem $awardItem): View
    {
        return view('Muasamcong::kqlcnt-awards.show', compact('awardItem'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);
        $ids = collect($request->input('selected_ids', []))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();
        $query = $this->query($filters)->orderByDesc('synced_from_catalog_at')->orderByDesc('id');
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }

        $rows = $query->get()->map(fn (KqlcntAwardItem $item): array => [
            $item->notify_no, $item->contractor_code, $item->contractor_name, $item->lot_no, $item->lot_name, $item->medicine_code,
            $item->medicine_name, $item->drug_group, $item->active_ingredient, $item->concentration, $item->route, $item->dosage_form,
            $item->packaging_spec, $item->shelf_life_months, $item->registration_or_import_license, $item->unit, $item->quantity,
            $item->price_plan, $item->winning_price, $item->amount, $item->manufacturer, $item->country, $item->decision_no,
            optional($item->decision_date)->format('d/m/Y'), optional($item->published_at)->format('d/m/Y H:i'), $item->investor_code,
            $item->investor_name, $item->contract_no, $item->source, $item->sync_source, $item->is_active ? 'Đang hoạt động' : 'Ngưng',
            optional($item->synced_from_catalog_at)->format('d/m/Y H:i:s'),
        ])->all();

        return Excel::download(new KqlcntArraySheetExport('Danh_muc_trung_thau', $this->headings(), $rows), 'KQLCNT-Awards-'.now()->format('Ymd-His').'.xlsx');
    }

    private function query(array $filters): Builder
    {
        return KqlcntAwardItem::query()
            ->whereNotNull('synced_from_catalog_at')
            ->when($filters['q'], function (Builder $query, string $q): void {
                $like = '%'.$q.'%';
                $query->where(function (Builder $query) use ($like): void {
                    foreach (['notify_no', 'contractor_code', 'contractor_name', 'medicine_code', 'medicine_name', 'active_ingredient', 'manufacturer', 'investor_name', 'decision_no', 'contract_no'] as $column) {
                        $query->orWhere($column, 'like', $like);
                    }
                });
            })
            ->when($filters['source'], fn (Builder $query, string $source) => $query->where('source', 'like', '%'.$source.'%'))
            ->when($filters['active'] !== '', fn (Builder $query) => $query->where('is_active', $filters['active'] === '1'))
            ->when($filters['published_from'], fn (Builder $query, string $date) => $query->whereDate('published_at', '>=', $date))
            ->when($filters['published_to'], fn (Builder $query, string $date) => $query->whereDate('published_at', '<=', $date))
            ->when($filters['synced_from'], fn (Builder $query, string $date) => $query->whereDate('synced_from_catalog_at', '>=', $date))
            ->when($filters['synced_to'], fn (Builder $query, string $date) => $query->whereDate('synced_from_catalog_at', '<=', $date));
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'], 'source' => ['nullable', 'string', 'max:100'], 'active' => ['nullable', 'in:0,1'],
            'published_from' => ['nullable', 'date'], 'published_to' => ['nullable', 'date'], 'synced_from' => ['nullable', 'date'], 'synced_to' => ['nullable', 'date'],
        ]);

        return [
            'q' => trim((string) ($validated['q'] ?? '')), 'source' => trim((string) ($validated['source'] ?? '')), 'active' => (string) ($validated['active'] ?? ''),
            'published_from' => $validated['published_from'] ?? '', 'published_to' => $validated['published_to'] ?? '',
            'synced_from' => $validated['synced_from'] ?? '', 'synced_to' => $validated['synced_to'] ?? '',
        ];
    }

    private function headings(): array
    {
        return ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Quy cách', 'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK', 'ĐVT', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Mã chủ đầu tư', 'Chủ đầu tư', 'Số hợp đồng', 'Nguồn dữ liệu', 'Nguồn đồng bộ', 'Trạng thái', 'Đồng bộ lúc'];
    }
}

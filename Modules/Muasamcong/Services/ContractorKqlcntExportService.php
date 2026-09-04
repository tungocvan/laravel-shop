<?php

namespace Modules\Muasamcong\Services;

use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\ContractorKqlcntWorkbookExport;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractorKqlcntExportService
{
    public function download(ContractorSearch $search, array $notifyNos = []): BinaryFileResponse
    {
        $scope = $search->items()->pluck('notify_no')->map(fn ($value) => trim((string) $value))->filter()->unique();
        $requested = collect($notifyNos)->map(fn ($value) => trim((string) $value))->filter()->unique();
        abort_if($requested->diff($scope)->isNotEmpty(), 422, 'Có Mã TBMT không thuộc lịch sử nhà thầu này.');
        $notifyNos = ($requested->isNotEmpty() ? $requested : $scope)->values()->all();

        $records = KqlcntRecord::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $notifyNos)
            ->get()->keyBy('notify_no');

        $awardItems = KqlcntAwardItem::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $notifyNos)
            ->orderBy('notify_no')->orderBy('lot_no')->get();

        $manualLots = ContractorManualLot::query()
            ->where('contractor_code', $search->contractor_code)
            ->whereIn('notify_no', $notifyNos)
            ->where('source', 'kqlcnt_verified')
            ->orderBy('notify_no')->orderBy('lot_no')->get();

        $normalizedKeys = $awardItems->map(fn ($item) => $item->notify_no.'|'.$item->lot_no)->flip();
        $apiRows = $manualLots->reject(fn ($lot) => isset($normalizedKeys[$lot->notify_no.'|'.$lot->lot_no]))->map(function ($lot): array {
            $raw = is_array($lot->raw_payload) ? $lot->raw_payload : [];

            return [
                $lot->notify_no, $raw['contractor_code'] ?? $lot->contractor_code, $raw['contractor_name'] ?? null,
                $lot->lot_no, $lot->lot_name, $raw['medicine_code'] ?? null, $lot->medicine_name,
                $raw['medicine_group'] ?? null, $lot->active_ingredient, $raw['concentration'] ?? null,
                $raw['route'] ?? null, $raw['dosage_form'] ?? null, $raw['uom'] ?? null,
                $lot->quantity !== null ? (float) $lot->quantity : null,
                $lot->price_plan !== null ? (float) $lot->price_plan : null,
                $lot->lot_price !== null ? (float) $lot->lot_price : null,
                $lot->quantity !== null && $lot->lot_price !== null ? (float) $lot->quantity * (float) $lot->lot_price : null,
                $raw['manufacturer'] ?? null, $raw['country'] ?? null, $raw['decision_no'] ?? null,
                $raw['decision_date'] ?? null, $raw['published_at'] ?? null, $raw['investor_name'] ?? null,
                $raw['contract_no'] ?? null, 'API', $lot->confirmed_at?->format('Y-m-d H:i:s'),
            ];
        });

        $importRows = $awardItems->map(fn ($item): array => [
            $item->notify_no, $item->contractor_code, $item->contractor_name, $item->lot_no, $item->lot_name,
            $item->medicine_code, $item->medicine_name, $item->drug_group, $item->active_ingredient, $item->concentration,
            $item->route, $item->dosage_form, $item->unit,
            $item->quantity !== null ? (float) $item->quantity : null,
            $item->price_plan !== null ? (float) $item->price_plan : null,
            $item->winning_price !== null ? (float) $item->winning_price : null,
            $item->amount !== null ? (float) $item->amount : null,
            $item->manufacturer, $item->country, $item->decision_no,
            $item->decision_date?->format('Y-m-d'), $item->published_at?->format('Y-m-d H:i:s'), $item->investor_name,
            $item->contract_no, strtoupper($item->source), $item->updated_at?->format('Y-m-d H:i:s'),
        ]);

        $overview = collect($notifyNos)->map(function (string $notifyNo) use ($records, $search, $awardItems, $manualLots): array {
            $record = $records->get($notifyNo);
            $count = $awardItems->where('notify_no', $notifyNo)->count() + $manualLots->where('notify_no', $notifyNo)->count();

            return [
                $notifyNo, $search->contractor_code, $search->contractor_name, $record?->bid_name, $record?->investor_name,
                $record?->status, $record?->published ? 'Có' : 'Không', $record?->current_contractor_won ? 'Có' : 'Không',
                is_array($record?->contracts) ? count($record->contracts) : 0, $count, strtoupper((string) ($record?->data_source ?: 'unknown')),
                $record?->synced_at?->format('Y-m-d H:i:s'), $record?->imported_at?->format('Y-m-d H:i:s'),
            ];
        })->all();

        $contracts = $records->flatMap(function ($record) {
            return collect((array) $record->contracts)->map(fn ($contract) => [
                $record->notify_no, $contract['contractNo'] ?? null, $record->contractor_code,
                $record->contractor_name, $record->investor_name, $contract['contractValue'] ?? null,
                $contract['startDate'] ?? null, $contract['endDate'] ?? null, strtoupper((string) ($record->data_source ?: 'api')),
            ]);
        })->values()->all();

        $winners = $records->flatMap(function ($record) {
            return collect((array) $record->all_winners)->map(fn ($winner) => [
                $record->notify_no, $winner['contractorCode'] ?? null, $winner['contractorName'] ?? null,
                $winner['contractorAddress'] ?? null, implode('; ', (array) ($winner['contracts'] ?? [])),
            ]);
        })->values()->all();

        $sheets = [
            ['title' => 'Tong_quan_KQLCNT', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Tên gói thầu', 'Chủ đầu tư / BMT', 'Trạng thái', 'Đã công bố', 'Nhà thầu trúng?', 'Số hợp đồng', 'Số lô/thuốc', 'Nguồn dữ liệu', 'Đồng bộ API lúc', 'Import lúc'], 'rows' => $overview],
            ['title' => 'Danh_muc_trung_thau', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Nồng độ / Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Đơn vị tính', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Cơ sở sản xuất', 'Nước sản xuất', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Chủ đầu tư / BMT', 'Số hợp đồng', 'Nguồn dữ liệu', 'Cập nhật lúc'], 'rows' => $importRows->concat($apiRows)->values()->all()],
            ['title' => 'Hop_dong', 'headings' => ['Mã TBMT', 'Số hợp đồng', 'Mã nhà thầu', 'Tên nhà thầu', 'Chủ đầu tư / BMT', 'Giá trị hợp đồng', 'Ngày hiệu lực', 'Ngày kết thúc', 'Nguồn dữ liệu'], 'rows' => $contracts],
            ['title' => 'Nha_thau_trung', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Địa chỉ', 'Hợp đồng liên quan'], 'rows' => $winners],
        ];

        $filename = 'KQLCNT-'.$search->contractor_code.'-search-'.$search->id.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ContractorKqlcntWorkbookExport($sheets), $filename);
    }
}

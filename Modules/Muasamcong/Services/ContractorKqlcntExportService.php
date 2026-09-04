<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\ContractorKqlcntWorkbookExport;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractorKqlcntExportService
{
    public function __construct(private readonly ContractorAwardCatalogService $catalog) {}

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

        $savedCatalog = $this->catalog->rows($search->contractor_code, $notifyNos);

        $normalizedKeys = $awardItems
            ->filter(fn ($item) => trim((string) $item->lot_no) !== '')
            ->map(fn ($item) => $this->lotKey($item->notify_no, $item->lot_no))
            ->flip();

        $savedCatalog = $savedCatalog
            ->reject(fn (array $row) => trim((string) ($row['lot_no'] ?? '')) !== ''
                && isset($normalizedKeys[$this->lotKey($row['notify_no'] ?? null, $row['lot_no'] ?? null)]))
            ->values();

        $savedKeys = $savedCatalog
            ->filter(fn (array $row) => trim((string) ($row['lot_no'] ?? '')) !== '')
            ->map(fn (array $row) => $this->lotKey($row['notify_no'] ?? null, $row['lot_no'] ?? null))
            ->flip();

        $snapshotRows = $this->snapshotApiRows($records, $search, $normalizedKeys, $savedKeys);

        $savedRows = $savedCatalog->map(fn (array $row): array => [
            $row['notify_no'] ?? null,
            $row['contractor_code'] ?? $search->contractor_code,
            $row['contractor_name'] ?? $search->contractor_name,
            $row['lot_no'] ?? null,
            $row['lot_name'] ?? null,
            $row['medicine_code'] ?? null,
            $row['medicine_name'] ?? null,
            $row['drug_group'] ?? null,
            $row['active_ingredient'] ?? null,
            $row['concentration'] ?? null,
            $row['route'] ?? null,
            $row['dosage_form'] ?? null,
            $row['unit'] ?? null,
            $row['quantity'] ?? null,
            $row['price_plan'] ?? null,
            $row['winning_price'] ?? null,
            $row['amount'] ?? null,
            $row['manufacturer'] ?? null,
            $row['country'] ?? null,
            $row['decision_no'] ?? null,
            $row['decision_date'] ?? null,
            $row['published_at'] ?? null,
            $row['investor_name'] ?? null,
            $row['contract_no'] ?? null,
            $row['source'] ?? 'SAVED',
            $row['updated_at'] ?? null,
        ]);

        $importRows = $awardItems->map(fn ($item): array => [
            $item->notify_no,
            $item->contractor_code,
            $item->contractor_name,
            $item->lot_no,
            $item->lot_name,
            $item->medicine_code,
            $item->medicine_name,
            $item->drug_group,
            $item->active_ingredient,
            $item->concentration,
            $item->route,
            $item->dosage_form,
            $item->unit,
            $item->quantity !== null ? (float) $item->quantity : null,
            $item->price_plan !== null ? (float) $item->price_plan : null,
            $item->winning_price !== null ? (float) $item->winning_price : null,
            $item->amount !== null ? (float) $item->amount : null,
            $item->manufacturer,
            $item->country,
            $item->decision_no,
            $item->decision_date?->format('Y-m-d'),
            $item->published_at?->format('Y-m-d H:i:s'),
            $item->investor_name,
            $item->contract_no,
            strtoupper($item->source),
            $item->updated_at?->format('Y-m-d H:i:s'),
        ]);

        $detailRows = $importRows->concat($savedRows)->concat($snapshotRows)->values();

        $overview = collect($notifyNos)->map(function (string $notifyNo) use ($records, $search, $detailRows): array {
            $record = $records->get($notifyNo);
            $count = $detailRows->filter(fn (array $row) => ($row[0] ?? null) === $notifyNo)->count();

            return [
                $notifyNo,
                $search->contractor_code,
                $search->contractor_name,
                $record?->bid_name,
                $record?->investor_name,
                $record?->status,
                $record?->published ? 'Có' : 'Không',
                $record?->current_contractor_won ? 'Có' : 'Không',
                is_array($record?->contracts) ? count($record->contracts) : 0,
                $count,
                strtoupper((string) ($record?->data_source ?: 'unknown')),
                $record?->synced_at?->format('Y-m-d H:i:s'),
                $record?->imported_at?->format('Y-m-d H:i:s'),
            ];
        })->all();

        $contracts = $records->flatMap(function ($record) {
            return collect((array) $record->contracts)->map(fn ($contract) => [
                $record->notify_no,
                $contract['contractNo'] ?? null,
                $record->contractor_code,
                $contract['contractorName'] ?? $contract['newContractorName'] ?? null,
                $record->investor_name,
                $contract['contractValue'] ?? $contract['priceAfter'] ?? null,
                $contract['contractEffectiveDate'] ?? $contract['startDate'] ?? null,
                $contract['endDate'] ?? null,
                strtoupper((string) ($record->data_source ?: 'api')),
            ]);
        })->values()->all();

        $winners = $records->flatMap(function ($record) {
            return collect((array) $record->all_winners)->map(fn ($winner) => [
                $record->notify_no,
                $winner['contractorCode'] ?? null,
                $winner['contractorName'] ?? null,
                $winner['contractorAddress'] ?? null,
                implode('; ', (array) ($winner['contracts'] ?? [])),
            ]);
        })->values()->all();

        $sheets = [
            ['title' => 'Tong_quan_KQLCNT', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Tên gói thầu', 'Chủ đầu tư / BMT', 'Trạng thái', 'Đã công bố', 'Nhà thầu trúng?', 'Số hợp đồng', 'Số lô/thuốc', 'Nguồn dữ liệu', 'Đồng bộ API lúc', 'Import lúc'], 'rows' => $overview],
            ['title' => 'Danh_muc_trung_thau', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Nồng độ / Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Đơn vị tính', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Cơ sở sản xuất', 'Nước sản xuất', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Chủ đầu tư / BMT', 'Số hợp đồng', 'Nguồn dữ liệu', 'Cập nhật lúc'], 'rows' => $detailRows->all()],
            ['title' => 'Hop_dong', 'headings' => ['Mã TBMT', 'Số hợp đồng', 'Mã nhà thầu', 'Tên nhà thầu', 'Chủ đầu tư / BMT', 'Giá trị hợp đồng', 'Ngày hiệu lực', 'Ngày kết thúc', 'Nguồn dữ liệu'], 'rows' => $contracts],
            ['title' => 'Nha_thau_trung', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Địa chỉ', 'Hợp đồng liên quan'], 'rows' => $winners],
        ];

        $filename = 'KQLCNT-'.$search->contractor_code.'-search-'.$search->id.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new ContractorKqlcntWorkbookExport($sheets), $filename);
    }

    private function snapshotApiRows(Collection $records, ContractorSearch $search, Collection $normalizedKeys, Collection $savedKeys): Collection
    {
        return $records->flatMap(function (KqlcntRecord $record) use ($search, $normalizedKeys, $savedKeys): array {
            $rows = [];

            foreach ((array) $record->verified_lots as $lot) {
                if (! is_array($lot)) {
                    continue;
                }

                $lotNo = trim((string) ($lot['lotNo'] ?? $lot['lotCode'] ?? $lot['id'] ?? ''));
                if ($lotNo === '') {
                    continue;
                }

                $key = $this->lotKey($record->notify_no, $lotNo);
                if (isset($normalizedKeys[$key]) || isset($savedKeys[$key])) {
                    continue;
                }

                $quantity = $this->number($lot['quantity'] ?? $lot['qty'] ?? null);
                $pricePlan = $this->number($lot['pricePlan'] ?? $lot['price_plan'] ?? $lot['unitPrice'] ?? null);
                $winningPrice = $this->number($lot['lotPrice'] ?? $lot['bidWinningPrice'] ?? $lot['winningPrice'] ?? null);
                $amount = $this->number($lot['amount'] ?? $lot['totalAmount'] ?? null);

                if ($amount === null && $quantity !== null && $winningPrice !== null) {
                    $amount = $quantity * $winningPrice;
                }

                $rows[] = [
                    $record->notify_no,
                    $lot['contractorCode'] ?? $lot['winningCode'] ?? $record->contractor_code,
                    $lot['contractorName'] ?? $lot['winningName'] ?? $search->contractor_name,
                    $lotNo,
                    $lot['lotName'] ?? $lot['medicineName'] ?? $lot['tenThuoc'] ?? null,
                    $lot['medicineCode'] ?? $lot['medicine_code'] ?? $lot['drugCode'] ?? null,
                    $lot['medicineName'] ?? $lot['tenThuoc'] ?? $lot['lotName'] ?? null,
                    $lot['medicineGroup'] ?? $lot['medicine_group'] ?? $lot['groupName'] ?? null,
                    $lot['activeIngredient'] ?? $lot['tenHoatChat'] ?? null,
                    $lot['concentration'] ?? $lot['strength'] ?? $lot['hamLuong'] ?? null,
                    $lot['route'] ?? $lot['routeName'] ?? $lot['duongDung'] ?? null,
                    $lot['dosageForm'] ?? $lot['dosage_form'] ?? $lot['dangBaoChe'] ?? null,
                    $lot['uom'] ?? $lot['unit'] ?? $lot['donViTinh'] ?? null,
                    $quantity,
                    $pricePlan,
                    $winningPrice,
                    $amount,
                    $lot['manufacturer'] ?? $lot['manufacturerName'] ?? $lot['producerName'] ?? null,
                    $lot['country'] ?? $lot['countryName'] ?? null,
                    $lot['decisionNo'] ?? $lot['decision_no'] ?? null,
                    $lot['decisionDate'] ?? $lot['decision_date'] ?? null,
                    $lot['publishedAt'] ?? $lot['published_at'] ?? null,
                    $lot['investorName'] ?? $record->investor_name,
                    $lot['contractNo'] ?? $lot['contract_no'] ?? null,
                    'API',
                    $record->synced_at?->format('Y-m-d H:i:s'),
                ];
            }

            return $rows;
        })->values();
    }

    private function lotKey(mixed $notifyNo, mixed $lotNo): string
    {
        return trim((string) $notifyNo).'|'.trim((string) $lotNo);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}

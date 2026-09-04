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

        $importRows = $awardItems->map(fn ($item): array => [
            $item->notify_no, $item->contractor_code, $item->contractor_name,
            $item->lot_no, $item->lot_name, $item->medicine_code, $item->medicine_name,
            $item->drug_group, $item->active_ingredient, $item->concentration, $item->route,
            $item->dosage_form, $item->unit,
            $item->quantity !== null ? (float) $item->quantity : null,
            $item->price_plan !== null ? (float) $item->price_plan : null,
            $item->winning_price !== null ? (float) $item->winning_price : null,
            $item->amount !== null ? (float) $item->amount : null,
            $item->manufacturer, $item->country, $item->decision_no,
            $item->decision_date?->format('Y-m-d'), $item->published_at?->format('Y-m-d H:i:s'),
            $item->investor_name, $item->contract_no,
            $item->packaging_spec, $item->shelf_life_months, $item->registration_or_import_license,
            strtoupper($item->source), $item->updated_at?->format('Y-m-d H:i:s'),
        ]);

        $savedRows = $savedCatalog->map(fn (array $row): array => [
            $row['notify_no'] ?? null, $row['contractor_code'] ?? $search->contractor_code,
            $row['contractor_name'] ?? $search->contractor_name, $row['lot_no'] ?? null,
            $row['lot_name'] ?? null, $row['medicine_code'] ?? null, $row['medicine_name'] ?? null,
            $row['drug_group'] ?? null, $row['active_ingredient'] ?? null, $row['concentration'] ?? null,
            $row['route'] ?? null, $row['dosage_form'] ?? null, $row['unit'] ?? null,
            $row['quantity'] ?? null, $row['price_plan'] ?? null, $row['winning_price'] ?? null,
            $row['amount'] ?? null, $row['manufacturer'] ?? null, $row['country'] ?? null,
            $row['decision_no'] ?? null, $row['decision_date'] ?? null, $row['published_at'] ?? null,
            $row['investor_name'] ?? null, $row['contract_no'] ?? null,
            $row['packaging_spec'] ?? null, $row['shelf_life_months'] ?? null, $row['registration_or_import_license'] ?? null,
            $row['source'] ?? 'SAVED', $row['updated_at'] ?? null,
        ]);

        $knownKeys = $importRows->concat($savedRows)
            ->filter(fn (array $row) => trim((string) ($row[3] ?? '')) !== '')
            ->map(fn (array $row) => $this->lotKey($row[0] ?? null, $row[3] ?? null))->flip();
        $snapshotRows = $this->snapshotApiRows($records, $search, $knownKeys);

        $detailRows = $this->mergeLogicalAwardRows($importRows->concat($savedRows)->concat($snapshotRows))
            ->map(fn (array $row): array => $this->enrichDetailRow($this->withCalculatedAmount($row), $records))
            ->values();

        $overview = collect($notifyNos)->map(function (string $notifyNo) use ($records, $search, $detailRows): array {
            $record = $records->get($notifyNo);
            $count = $detailRows->where(0, $notifyNo)->count();

            return [$notifyNo, $search->contractor_code, $search->contractor_name, $record?->bid_name,
                $record?->investor_name, $record?->status, $record?->published ? 'Có' : 'Không',
                $record?->current_contractor_won ? 'Có' : 'Không', is_array($record?->contracts) ? count($record->contracts) : 0,
                $count, strtoupper((string) ($record?->data_source ?: 'unknown')),
                $record?->synced_at?->format('Y-m-d H:i:s'), $record?->imported_at?->format('Y-m-d H:i:s')];
        })->all();

        $contracts = $records->flatMap(function ($record) use ($detailRows) {
            $recordContracts = collect((array) $record->contracts)->values();
            $recordDetails = $detailRows->where(0, $record->notify_no)->values();
            $contractCount = $recordContracts->count();

            return $recordContracts->map(function ($contract) use ($record, $recordDetails, $contractCount): array {
                $contractNo = trim((string) ($contract['contractNo'] ?? ''));
                $matched = $contractNo !== '' ? $recordDetails->filter(fn (array $row) => trim((string) ($row[23] ?? '')) === $contractNo) : collect();
                $amount = $matched->isNotEmpty() ? $this->sumAmounts($matched) : ($contractCount === 1 ? $this->sumAmounts($recordDetails) : null);

                return [$record->notify_no, $contract['contractNo'] ?? null, $record->contractor_code,
                    $contract['contractorName'] ?? $contract['newContractorName'] ?? null, $record->investor_name,
                    $amount, $contract['contractEffectiveDate'] ?? $contract['startDate'] ?? null,
                    $contract['endDate'] ?? null, strtoupper((string) ($record->data_source ?: 'api'))];
            });
        })->values()->all();

        $winners = $records->flatMap(fn ($record) => collect((array) $record->all_winners)->map(fn ($winner) => [
            $record->notify_no, $winner['contractorCode'] ?? null, $winner['contractorName'] ?? null,
            $winner['contractorAddress'] ?? null, implode('; ', (array) ($winner['contracts'] ?? [])),
        ]))->values()->all();

        $sheets = [
            ['title' => 'Tong_quan_KQLCNT', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Tên gói thầu', 'Chủ đầu tư / BMT', 'Trạng thái', 'Đã công bố', 'Nhà thầu trúng?', 'Số hợp đồng', 'Số lô/thuốc', 'Nguồn dữ liệu', 'Đồng bộ API lúc', 'Import lúc'], 'rows' => $overview],
            ['title' => 'Danh_muc_trung_thau', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Nồng độ / Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Đơn vị tính', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Cơ sở sản xuất', 'Nước sản xuất', 'Số quyết định', 'Ngày quyết định', 'Ngày đăng KQLCNT', 'Chủ đầu tư / BMT', 'Số hợp đồng', 'Quy cách', 'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK', 'Nguồn dữ liệu', 'Cập nhật lúc'], 'rows' => $detailRows->all()],
            ['title' => 'Hop_dong', 'headings' => ['Mã TBMT', 'Số hợp đồng', 'Mã nhà thầu', 'Tên nhà thầu', 'Chủ đầu tư / BMT', 'Giá trị hợp đồng', 'Ngày hiệu lực', 'Ngày kết thúc', 'Nguồn dữ liệu'], 'rows' => $contracts],
            ['title' => 'Nha_thau_trung', 'headings' => ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Địa chỉ', 'Hợp đồng liên quan'], 'rows' => $winners],
        ];

        return Excel::download(new ContractorKqlcntWorkbookExport($sheets), 'KQLCNT-'.$search->contractor_code.'-search-'.$search->id.'-'.now()->format('Ymd-His').'.xlsx');
    }

    private function mergeLogicalAwardRows(Collection $rows): Collection
    {
        $merged = [];
        $unkeyed = [];

        foreach ($rows as $row) {
            $lotNo = trim((string) ($row[3] ?? ''));
            if ($lotNo === '') {
                $unkeyed[] = $row;
                continue;
            }

            $key = $this->lotKey($row[0] ?? null, $lotNo);
            if (! isset($merged[$key])) {
                $merged[$key] = $row;
                continue;
            }

            foreach ($row as $index => $value) {
                if ($this->blank($merged[$key][$index] ?? null) && ! $this->blank($value)) {
                    $merged[$key][$index] = $value;
                }
            }

            $sources = collect([$merged[$key][27] ?? null, $row[27] ?? null])
                ->filter()->flatMap(fn ($value) => preg_split('/\+/', (string) $value) ?: [])
                ->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
            $merged[$key][27] = $sources->implode('+');
        }

        return collect(array_values($merged))->concat($unkeyed);
    }

    private function enrichDetailRow(array $row, Collection $records): array
    {
        $record = $records->get((string) ($row[0] ?? ''));
        if (! $record) {
            return $row;
        }

        if ($this->blank($row[22] ?? null)) {
            $row[22] = $record->investor_name;
        }

        if ($this->blank($row[23] ?? null)) {
            $contracts = collect((array) $record->contracts)
                ->pluck('contractNo')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
            if ($contracts->count() === 1) {
                $row[23] = $contracts->first();
            }
        }

        return $row;
    }

    private function snapshotApiRows(Collection $records, ContractorSearch $search, Collection $knownKeys): Collection
    {
        return $records->flatMap(function (KqlcntRecord $record) use ($search, $knownKeys): array {
            $rows = [];
            foreach ((array) $record->verified_lots as $lot) {
                if (! is_array($lot)) {
                    continue;
                }
                $raw = is_array($lot['raw_payload'] ?? null) ? $lot['raw_payload'] : [];
                $lot = array_replace($raw, $lot);
                $lotNo = trim((string) ($lot['lotNo'] ?? $lot['lotCode'] ?? $lot['id'] ?? $lot['lot_no'] ?? ''));
                if ($lotNo === '' || isset($knownKeys[$this->lotKey($record->notify_no, $lotNo)])) {
                    continue;
                }
                $quantity = $this->number($lot['quantity'] ?? $lot['qty'] ?? null);
                $pricePlan = $this->number($lot['pricePlan'] ?? $lot['price_plan'] ?? $lot['unitPrice'] ?? null);
                $winningPrice = $this->number($lot['lotPrice'] ?? $lot['bidWinningPrice'] ?? $lot['winningPrice'] ?? $lot['winning_price'] ?? null);
                $amount = $this->number($lot['amount'] ?? $lot['totalAmount'] ?? null) ?? (($quantity !== null && $winningPrice !== null) ? $quantity * $winningPrice : null);
                $rows[] = [$record->notify_no, $lot['contractorCode'] ?? $lot['winningCode'] ?? $record->contractor_code,
                    $lot['contractorName'] ?? $lot['winningName'] ?? $search->contractor_name, $lotNo,
                    $lot['lotName'] ?? $lot['medicineName'] ?? $lot['tenThuoc'] ?? null,
                    $lot['medicineCode'] ?? $lot['medicine_code'] ?? $lot['drugCode'] ?? null,
                    $lot['medicineName'] ?? $lot['tenThuoc'] ?? $lot['lotName'] ?? null,
                    $lot['medicineGroup'] ?? $lot['medicine_group'] ?? $lot['groupName'] ?? null,
                    $lot['activeIngredient'] ?? $lot['tenHoatChat'] ?? null,
                    $lot['concentration'] ?? $lot['strength'] ?? $lot['hamLuong'] ?? null,
                    $lot['route'] ?? $lot['routeName'] ?? $lot['duongDung'] ?? null,
                    $lot['dosageForm'] ?? $lot['dosage_form'] ?? $lot['dangBaoChe'] ?? null,
                    $lot['uom'] ?? $lot['unit'] ?? $lot['donViTinh'] ?? null, $quantity, $pricePlan, $winningPrice, $amount,
                    $lot['manufacturer'] ?? $lot['manufacturerName'] ?? $lot['producerName'] ?? null,
                    $lot['country'] ?? $lot['countryName'] ?? $lot['nuocSanXuat'] ?? null, $lot['decisionNo'] ?? $lot['decision_no'] ?? null,
                    $lot['decisionDate'] ?? $lot['decision_date'] ?? null, $lot['publishedAt'] ?? $lot['published_at'] ?? null,
                    $lot['investorName'] ?? $record->investor_name, $lot['contractNo'] ?? $lot['contract_no'] ?? null,
                    $lot['packaging_spec'] ?? $lot['packagingSpec'] ?? $lot['packing'] ?? $lot['quyCach'] ?? $lot['quyCachDongGoi'] ?? null,
                    $this->wholeNumber($lot['shelf_life_months'] ?? $lot['shelfLifeMonths'] ?? $lot['hanDungThang'] ?? $lot['hanDung'] ?? null),
                    $lot['registration_or_import_license'] ?? $lot['registrationNo'] ?? $lot['registrationNumber'] ?? $lot['gdkLH'] ?? $lot['gpnk'] ?? $lot['soDangKy'] ?? null,
                    'API', $record->synced_at?->format('Y-m-d H:i:s')];
            }

            return $rows;
        })->values();
    }

    private function withCalculatedAmount(array $row): array
    {
        $amount = $this->number($row[16] ?? null);
        if ($amount !== null) {
            $row[16] = $amount;

            return $row;
        }
        $quantity = $this->number($row[13] ?? null);
        $winningPrice = $this->number($row[15] ?? null);
        if ($quantity !== null && $winningPrice !== null) {
            $row[16] = $quantity * $winningPrice;
        }

        return $row;
    }

    private function sumAmounts(Collection $rows): ?float
    {
        $amounts = $rows->map(fn (array $row): ?float => $this->number($row[16] ?? null))->filter(fn (?float $amount): bool => $amount !== null);

        return $amounts->isEmpty() ? null : (float) $amounts->sum();
    }

    private function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function lotKey(mixed $notifyNo, mixed $lotNo): string
    {
        return trim((string) $notifyNo).'|'.trim((string) $lotNo);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function wholeNumber(mixed $value): ?int
    {
        $number = $this->number($value);

        return $number === null ? null : max(0, (int) round($number));
    }
}

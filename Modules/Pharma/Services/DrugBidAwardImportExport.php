<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\Medicine;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class DrugBidAwardImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'drug_bid_awards';

    protected string $mode = 'update_or_create';

    protected bool $ignoreNullValuesOnUpdate = true;

    protected array $uniqueBy = ['bidding_notice_code', 'medicine_name', 'winning_company_name'];

    protected array $rules = [
        'medicine_id' => ['nullable', 'integer', 'exists:pharma_medicines,id'],
        'medicine_name' => ['required', 'string', 'max:255'],
        'packaging_specification' => ['required', 'string', 'max:255'],
        'quantity' => ['required', 'integer', 'min:0'],
        'unit_price' => ['required', 'numeric', 'min:0'],
        'bidding_notice_code' => ['required', 'string', 'max:255'],
        'investor_name' => ['required', 'string', 'max:255'],
        'decision_number' => ['required', 'string', 'max:255'],
        'decision_date' => ['required', 'date'],
        'contract_duration_months' => ['required', 'integer', 'min:0'],
        'winning_company_name' => ['required', 'string', 'max:255'],
        'decision_document_url' => ['nullable', 'url'],
    ];

    protected function modelClass(): string
    {
        return DrugBidAward::class;
    }

    protected function csvDelimiter(): string
    {
        return ';';
    }

    public function columnMapping(): array
    {
        return [
            'A' => 'bidding_notice_code',
            'B' => 'investor_name',
            'C' => 'decision_number',
            'D' => 'decision_date',
            'E' => 'contract_duration_months',
            'F' => 'medicine_name',
            'G' => 'medicine_code',
            'H' => 'packaging_specification',
            'I' => 'quantity',
            'J' => 'unit_price',
            'K' => 'amount',
            'L' => 'winning_company_name',
            'M' => 'medicine_match_status',
            'N' => 'decision_document_url',
        ];
    }

    public function export(array $filters = []): string
    {
        $path = $this->makeExportPath(class_basename($this->modelClass()));
        $awards = $this->exportRows($filters);

        $spreadsheet = new Spreadsheet;
        $productSheet = $spreadsheet->getActiveSheet();
        $productSheet->setTitle('Sản phẩm trúng thầu');

        $productRows = $awards->map(fn (DrugBidAward $award): array => $this->mapExportRow($award))->values();
        $this->writeWorksheet($productSheet, $productRows);

        $awardIds = $awards->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $allocations = $awardIds === []
            ? collect()
            : DrugBidAwardAllocation::query()
                ->with(['award.medicine', 'partner'])
                ->whereIn('drug_bid_award_id', $awardIds)
                ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
                ->orderBy('drug_bid_award_id')
                ->orderBy('partner_id')
                ->get();

        if ($allocations->isNotEmpty()) {
            $allocationSheet = $spreadsheet->createSheet();
            $allocationSheet->setTitle('Phân bổ bệnh viện');
            $allocationRows = $allocations->map(fn (DrugBidAwardAllocation $allocation): array => $this->mapAllocationExportRow($allocation))->values();
            $this->writeWorksheet($allocationSheet, $allocationRows);
        }

        (new Xlsx($spreadsheet))->save($this->exportAbsolutePath($path));
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function import(string $filePath, array $options = []): array
    {
        $report = parent::import($filePath, $options);

        if (($report['success'] ?? false) !== true
            || (bool) ($options['dry_run'] ?? false)
            || strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'xlsx') {
            return $report;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getSheetByName('Phân bổ bệnh viện');

            if (! $sheet) {
                $spreadsheet->disconnectWorksheets();

                return $report;
            }

            $rows = $sheet->toArray(null, true, true, false);
            $headers = array_map(fn ($value): string => trim((string) $value), array_shift($rows) ?? []);
            $allocationService = app(DrugBidAwardAllocationService::class);
            $adminId = auth('admin')->id();

            foreach ($rows as $index => $values) {
                if (count(array_filter($values, fn ($value) => $value !== null && $value !== '')) === 0) {
                    continue;
                }

                $row = array_combine($headers, array_pad($values, count($headers), null));
                $tbmt = trim((string) ($row['Mã TBMT'] ?? ''));
                $medicineCode = trim((string) ($row['Mã sản phẩm chuẩn'] ?? ''));
                $medicineName = trim((string) ($row['Tên sản phẩm'] ?? ''));
                $partnerId = (int) ($row['Partner ID'] ?? 0);
                $quantity = $this->vietnameseNumber($row['Số lượng phân bổ'] ?? null);

                $award = DrugBidAward::query()
                    ->where('bidding_notice_code', $tbmt)
                    ->when(
                        $medicineCode !== '',
                        fn ($query) => $query->where('medicine_code', $medicineCode),
                        fn ($query) => $query->where('medicine_name', $medicineName)
                    )
                    ->first();

                $partner = $partnerId > 0 ? Partner::query()->find($partnerId) : null;

                if (! $award || ! $partner || $quantity === null || $quantity <= 0) {
                    throw new \RuntimeException('Dòng '.($index + 2).' của sheet Phân bổ bệnh viện không xác định được sản phẩm, bệnh viện hoặc số lượng.');
                }

                $existingAllocationId = DrugBidAwardAllocation::query()
                    ->where('drug_bid_award_id', $award->id)
                    ->where('partner_id', $partner->id)
                    ->value('id');

                $allocationService->save($award->id, $existingAllocationId ? (int) $existingAllocationId : null, [
                    'partner_id' => $partner->id,
                    'allocated_quantity' => $quantity,
                    'notes' => $row['Ghi chú'] ?? null,
                ], $adminId);
            }

            $spreadsheet->disconnectWorksheets();

            return $report;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('Phân bổ bệnh viện', null, null, 'Import sản phẩm thành công nhưng không thể import đầy đủ sheet phân bổ: '.$exception->getMessage());

            return $this->report(false);
        }
    }

    private function mapAllocationExportRow(DrugBidAwardAllocation $allocation): array
    {
        $award = $allocation->award;
        $medicineCode = $award?->medicine?->medicine_code ?: $award?->medicine_code;

        return [
            'Mã TBMT' => $award?->bidding_notice_code,
            'Mã sản phẩm chuẩn' => $medicineCode,
            'Tên sản phẩm' => $award?->medicine_name,
            'Partner ID' => $allocation->partner_id,
            'Tên bệnh viện' => $allocation->partner?->name,
            'Số lượng trúng' => $award?->quantity !== null ? (float) $award->quantity : null,
            'Số lượng phân bổ' => $allocation->allocated_quantity !== null ? (float) $allocation->allocated_quantity : null,
            'Trạng thái phân bổ' => $allocation->status,
            'Hiệu lực từ' => $allocation->effective_from?->format('Y-m-d'),
            'Hiệu lực đến' => $allocation->effective_until?->format('Y-m-d'),
            'Ghi chú' => $allocation->notes,
        ];
    }

    private function writeWorksheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headers = array_keys($rows->first());
        $sheet->fromArray($headers, null, 'A1');

        foreach ($rows as $index => $row) {
            $sheet->fromArray(array_values($row), null, 'A'.($index + 2));
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
    }

    protected function normalizeRow(array $row): array
    {
        $medicineCode = $this->cleanString($row['medicine_code'] ?? null);

        $data = [
            'medicine_name' => $this->cleanString($row['medicine_name'] ?? null),
            'packaging_specification' => $this->cleanString($row['packaging_specification'] ?? null),
            'quantity' => $this->vietnameseInteger($row['quantity'] ?? null),
            'unit_price' => $this->vietnameseNumber($row['unit_price'] ?? null),
            'bidding_notice_code' => $this->cleanString($row['bidding_notice_code'] ?? null),
            'investor_name' => $this->cleanString($row['investor_name'] ?? null),
            'decision_number' => $this->cleanString($row['decision_number'] ?? null),
            'decision_date' => $this->cleanDate($row['decision_date'] ?? null),
            'contract_duration_months' => $this->months($row['contract_duration_months'] ?? null),
            'winning_company_name' => $this->cleanString($row['winning_company_name'] ?? null),
            'decision_document_url' => $this->cleanString($row['decision_document_url'] ?? null),
        ];

        if ($data['unit_price'] !== null) {
            $data['winning_price'] = $data['unit_price'];
        }

        if ($data['quantity'] !== null && $data['unit_price'] !== null) {
            $data['amount'] = $data['quantity'] * $data['unit_price'];
        }

        $existing = $this->existingRecord($data);
        if ($existing) {
            foreach ($data as $field => $value) {
                if ($value === null) {
                    $data[$field] = $existing->getAttribute($field);
                }
            }
        }

        $data['medicine_id'] = $existing?->medicine_id ?? $this->resolveMedicineId($data, $medicineCode);

        if ($data['medicine_id']) {
            $data['medicine_match_status'] = DrugBidAward::MATCH_VERIFIED;
            $data['medicine_code'] = Medicine::query()->whereKey($data['medicine_id'])->value('medicine_code');
        }

        return $data;
    }

    protected function exportRows(array $filters = []): Collection
    {
        $selectedIds = $this->selectedIds($filters);
        $lineageAvailable = Schema::hasTable('pharma_drug_bid_award_sources');

        if ($selectedIds !== []) {
            $representatives = DrugBidAward::query()
                ->whereKey($selectedIds)
                ->get(['id', 'bidding_notice_code']);

            $tbmtCodes = $representatives->pluck('bidding_notice_code')->filter()->unique()->values();
            $standaloneIds = $representatives->whereNull('bidding_notice_code')->pluck('id')->values();

            return $this->awardQuery($lineageAvailable)
                ->where(function ($query) use ($tbmtCodes, $standaloneIds): void {
                    if ($tbmtCodes->isNotEmpty()) {
                        $query->whereIn('bidding_notice_code', $tbmtCodes);
                    }

                    if ($standaloneIds->isNotEmpty()) {
                        $method = $tbmtCodes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $query->{$method}('id', $standaloneIds);
                    }
                })
                ->orderBy('bidding_notice_code')
                ->orderBy('id')
                ->get();
        }

        return $this->awardQuery($lineageAvailable)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$search}%")
                ->orWhere('active_ingredient', 'like', "%{$search}%")
                ->orWhere('medicine_code', 'like', "%{$search}%")
                ->orWhere('lot_name', 'like', "%{$search}%")
                ->orWhere('decision_number', 'like', "%{$search}%")))
            ->when($filters['tbmt'] ?? null, fn ($query, $tbmt) => $query->where('bidding_notice_code', 'like', "%{$tbmt}%"))
            ->when($filters['investor'] ?? null, fn ($query, $investor) => $query->where('investor_name', 'like', "%{$investor}%"))
            ->when($filters['company'] ?? null, fn ($query, $company) => $query->where('winning_company_name', 'like', "%{$company}%"))
            ->when($filters['source'] ?? null, function ($query, $source) use ($lineageAvailable): void {
                if (! $lineageAvailable) {
                    $query->where('source_type', $source);

                    return;
                }

                $query->where(fn ($sourceQuery) => $sourceQuery
                    ->where('source_type', $source)
                    ->orWhereHas('sources', fn ($lineageQuery) => $lineageQuery->where('source_system', $source)));
            })
            ->when($filters['medicine_match_status'] ?? null, fn ($query, $status) => $query->where('medicine_match_status', $status))
            ->latest('id')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        $medicineCode = $model->medicine?->medicine_code ?: $model->medicine_code;
        $unitPrice = $model->winning_price ?? $model->unit_price;
        $amount = $model->amount ?? ((float) $model->quantity * (float) $unitPrice);

        return [
            'Mã TBMT' => $model->bidding_notice_code,
            'Chủ đầu tư' => $model->investor_name,
            'Số quyết định' => $model->decision_number,
            'Ngày quyết định' => $model->decision_date?->format('Y-m-d'),
            'Thời gian HĐ (tháng)' => $model->contract_duration_months,
            'Tên sản phẩm trúng thầu' => $model->medicine_name,
            'Mã sản phẩm chuẩn' => $medicineCode,
            'Quy cách' => $model->packaging_specification,
            'Số lượng trúng' => $model->quantity === null ? null : (int) round((float) $model->quantity),
            'Đơn giá trúng' => $unitPrice === null ? null : (float) $unitPrice,
            'Giá trị' => (float) $amount,
            'Nhà thầu' => $model->winning_company_name,
            'Trạng thái đối soát HSSP' => $model->medicine_match_status,
            'Link quyết định trúng thầu' => $model->decision_document_url,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'Mã TBMT' => 'IB0123456789',
            'Chủ đầu tư' => 'Bệnh viện Quân y 175',
            'Số quyết định' => '4927/QĐ-BV',
            'Ngày quyết định' => '2025-10-13',
            'Thời gian HĐ (tháng)' => 24,
            'Tên sản phẩm trúng thầu' => 'Trosicam 15mg',
            'Mã sản phẩm chuẩn' => null,
            'Quy cách' => 'Hộp 3 vỉ x 10 viên',
            'Số lượng trúng' => 600000,
            'Đơn giá trúng' => 7791,
            'Giá trị' => 4674600000,
            'Nhà thầu' => 'Công ty TNHH Dược phẩm ABC',
            'Trạng thái đối soát HSSP' => DrugBidAward::MATCH_UNRESOLVED,
            'Link quyết định trúng thầu' => null,
        ];
    }

    private function awardQuery(bool $lineageAvailable): Builder
    {
        $query = DrugBidAward::query()->with('medicine');

        if ($lineageAvailable) {
            $query->with('sources');
        }

        return $query;
    }

    private function existingRecord(array $data): ?DrugBidAward
    {
        foreach ($this->uniqueBy as $field) {
            if (! ($data[$field] ?? null)) {
                return null;
            }
        }

        return DrugBidAward::query()->where(collect($this->uniqueBy)->mapWithKeys(fn ($field) => [$field => $data[$field]])->all())->first();
    }

    private function resolveMedicineId(array $data, ?string $medicineCode = null): ?int
    {
        if ($medicineCode) {
            $medicineId = Medicine::query()->where('medicine_code', $medicineCode)->value('id');

            if ($medicineId) {
                return (int) $medicineId;
            }
        }

        if (! $data['medicine_name']) {
            return null;
        }

        return Medicine::query()->where('name', $data['medicine_name'])
            ->when($data['packaging_specification'], fn ($query, $packaging) => $query->where('packaging_specification', $packaging))
            ->value('id');
    }

    private function selectedIds(array $filters): array
    {
        return collect($filters['selected_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function vietnameseNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = str_replace(['.', ',', ' '], '', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function vietnameseInteger(mixed $value): ?int
    {
        $number = $this->vietnameseNumber($value);

        return $number === null ? null : (int) $number;
    }

    private function months(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        preg_match('/\d+/', (string) $value, $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }
}

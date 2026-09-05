<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
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
            'B' => 'medicine_name', 'C' => 'packaging_specification', 'D' => 'quantity',
            'E' => 'unit_price', 'F' => 'bidding_notice_code', 'G' => 'investor_name',
            'H' => 'decision_number', 'I' => 'decision_date', 'J' => 'contract_duration_months',
            'K' => 'winning_company_name', 'L' => 'decision_document_url',
        ];
    }

    protected function normalizeRow(array $row): array
    {
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

        $existing = $this->existingRecord($data);
        if ($existing) {
            foreach ($data as $field => $value) {
                if ($value === null) {
                    $data[$field] = $existing->getAttribute($field);
                }
            }
        }

        $data['medicine_id'] = $existing?->medicine_id ?? $this->resolveMedicineId($data);

        return $data;
    }

    protected function exportRows(array $filters = []): Collection
    {
        $selectedIds = $this->selectedIds($filters);
        $lineageAvailable = Schema::hasTable('pharma_drug_bid_award_sources');

        if ($selectedIds !== []) {
            return $this->awardQuery($lineageAvailable)
                ->whereKey($selectedIds)
                ->latest('id')
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
        $name = $model->effectiveMedicineAttribute('medicine_name');
        $ingredient = $model->effectiveMedicineAttribute('active_ingredient');
        $strength = $model->effectiveMedicineAttribute('concentration');
        $route = $model->effectiveMedicineAttribute('route');
        $dosage = $model->effectiveMedicineAttribute('dosage_form');

        return [
            'Tên thuốc' => $name['value'],
            'Nguồn tên thuốc hiệu lực' => $name['origin'],
            'Hoạt chất' => $ingredient['value'],
            'Hàm lượng / nồng độ' => $strength['value'],
            'Dạng bào chế' => $dosage['value'],
            'Đường dùng' => $route['value'],
            'Quy cách đóng gói' => $model->packaging_specification,
            'Số lượng' => $model->quantity,
            'Đơn vị' => $model->unit,
            'Giá kế hoạch' => $model->price_plan,
            'Đơn giá trúng thầu' => $model->winning_price ?? $model->unit_price,
            'Thành tiền' => $model->amount,
            'Mã thông báo mời thầu' => $model->bidding_notice_code,
            'Số lô' => $model->lot_no,
            'Tên lô' => $model->lot_name,
            'Mã Chủ đầu tư' => $model->investor_code,
            'Tên Chủ đầu tư' => $model->investor_name,
            'Mã nhà thầu' => $model->contractor_code,
            'Công ty trúng thầu' => $model->winning_company_name,
            'Số quyết định' => $model->decision_number,
            'Ngày ban hành quyết định' => $model->decision_date?->format('d/m/Y'),
            'Số hợp đồng' => $model->contract_no,
            'Thời hạn hợp đồng' => $model->contract_period_text ?: ($model->contract_duration_months ? $model->contract_duration_months.' tháng' : null),
            'Nguồn dữ liệu' => $model->source_type,
            'Trạng thái đối soát HSSP' => $model->medicine_match_status,
            'Số nguồn lineage' => $model->relationLoaded('sources') ? $model->sources->count() : 0,
            'Link quyết định trúng thầu' => $model->decision_document_url,
        ];
    }

    protected function templateSampleRow(): array
    {
        return ['STT' => 1] + $this->mapExportRow(new DrugBidAward([
            'medicine_name' => 'Trosicam 15mg', 'packaging_specification' => 'Hộp 3 vỉ x 10 viên',
            'quantity' => 600000, 'unit_price' => 7791, 'bidding_notice_code' => 'IB0123456789',
            'investor_name' => 'Bệnh viện Quân y 175', 'decision_number' => '4927/QĐ-BV',
            'decision_date' => '2025-10-13', 'contract_duration_months' => 24,
            'winning_company_name' => 'Công ty TNHH Dược phẩm ABC',
        ]));
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

    private function resolveMedicineId(array $data): ?int
    {
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

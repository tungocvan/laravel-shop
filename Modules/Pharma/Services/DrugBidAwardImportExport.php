<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
        return DrugBidAward::query()->with('medicine')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$search}%")
                ->orWhere('bidding_notice_code', 'like', "%{$search}%")
                ->orWhere('decision_number', 'like', "%{$search}%")))
            ->when($filters['investor'] ?? null, fn ($query, $investor) => $query->where('investor_name', $investor))
            ->when($filters['company'] ?? null, fn ($query, $company) => $query->where('winning_company_name', $company))
            ->latest('id')->get();
    }

    protected function mapExportRow(Model $model): array
    {
        return [
            'Tên thuốc' => $model->medicine_name,
            'Quy cách đóng gói' => $model->packaging_specification,
            'Số lượng' => $model->quantity,
            'Đơn giá trúng thầu' => $model->unit_price,
            'Mã thông báo mời thầu' => $model->bidding_notice_code,
            'Tên Chủ đầu tư' => $model->investor_name,
            'Số quyết định' => $model->decision_number,
            'Ngày ban hành quyết định' => $model->decision_date?->format('d/m/Y'),
            'Thời hạn hiệu lực' => $model->contract_duration_months.' tháng',
            'Công ty trúng thầu' => $model->winning_company_name,
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

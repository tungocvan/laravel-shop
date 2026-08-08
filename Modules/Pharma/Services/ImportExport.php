<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'Theo_doi_nha_cung_cap';

    protected string $mode = 'update_or_create';

    protected bool $ignoreNullValuesOnUpdate = true;

    protected array $uniqueBy = ['medicine_id', 'supplier_name', 'working_date'];

    protected array $rules = [
        'medicine_id' => ['required', 'integer', 'exists:pharma_medicines,id'],
        'working_date' => ['required', 'date'],
        'supplier_name' => ['required', 'string', 'max:255'],
        'supplier_representative' => ['nullable', 'string', 'max:255'],
        'area' => ['nullable', 'string', 'max:255'],
        'import_price' => ['required', 'numeric', 'min:0'],
        'selling_price' => ['required', 'numeric', 'min:0'],
        'invoice_price' => ['required', 'numeric', 'min:0'],
        'invoice_difference_amount' => ['required', 'numeric'],
        'invoice_difference_percent' => ['required', 'numeric', 'min:0'],
        'invoice_difference_fee' => ['required', 'numeric'],
        'cost_price' => ['required', 'numeric'],
        'gross_profit_percent' => ['required', 'numeric'],
        'committed_quantity' => ['nullable', 'numeric', 'min:0'],
        'unit' => ['nullable', 'string', 'max:255'],
        'deposit_amount' => ['nullable', 'numeric', 'min:0'],
        'start_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        'contract_url' => ['nullable', 'url'],
        'status' => ['required', 'in:active,completed,paused,cancelled'],
        'note' => ['nullable', 'string'],
    ];

    protected function modelClass(): string
    {
        return SupplierTracking::class;
    }

    public function columnMapping(): array
    {
        return [
            'A' => 'working_date',
            'B' => 'medicine_name',
            'C' => 'registration_number',
            'D' => 'supplier_name',
            'E' => 'supplier_representative',
            'F' => 'area',
            'G' => 'import_price',
            'H' => 'selling_price',
            'I' => 'invoice_price',
            'J' => 'invoice_difference_amount',
            'K' => 'invoice_difference_percent',
            'L' => 'invoice_difference_fee',
            'M' => 'cost_price',
            'N' => 'gross_profit_percent',
            'O' => 'committed_quantity',
            'P' => 'unit',
            'Q' => 'deposit_amount',
            'R' => 'start_date',
            'S' => 'end_date',
            'T' => 'contract_url',
            'U' => 'status',
            'V' => 'note',
        ];
    }

    protected function normalizeRow(array $row): array
    {
        $medicine = $this->findMedicine(
            $this->cleanString($row['registration_number'] ?? null),
            $this->cleanString($row['medicine_name'] ?? null)
        );

        if (! $medicine) {
            throw new \RuntimeException('Không tìm thấy thuốc theo số đăng ký hoặc tên thuốc.');
        }

        $data = [
            'medicine_id' => $medicine->id,
            'working_date' => $this->cleanDate($row['working_date'] ?? null),
            'supplier_name' => $this->cleanString($row['supplier_name'] ?? null),
            'supplier_representative' => $this->cleanString($row['supplier_representative'] ?? null),
            'area' => $this->cleanString($row['area'] ?? null),
            'import_price' => $this->vietnameseNumber($row['import_price'] ?? null),
            'selling_price' => $this->vietnameseNumber($row['selling_price'] ?? null),
            'invoice_price' => $this->vietnameseNumber($row['invoice_price'] ?? null),
            'invoice_difference_percent' => $this->vietnameseNumber($row['invoice_difference_percent'] ?? null),
            'committed_quantity' => $this->vietnameseNumber($row['committed_quantity'] ?? null),
            'unit' => $this->cleanString($row['unit'] ?? null),
            'deposit_amount' => $this->vietnameseNumber($row['deposit_amount'] ?? null),
            'start_date' => $this->cleanDate($row['start_date'] ?? null),
            'end_date' => $this->cleanDate($row['end_date'] ?? null),
            'contract_url' => $this->cleanString($row['contract_url'] ?? null),
            'status' => $this->normalizeStatus($row['status'] ?? null),
            'note' => $this->cleanString($row['note'] ?? null),
        ];

        $existing = $this->existingRecord($data);
        if ($existing) {
            foreach ($data as $field => $value) {
                if ($value === null) {
                    $data[$field] = $existing->getAttribute($field);
                }
            }
        } else {
            $data['import_price'] ??= 0;
            $data['selling_price'] ??= 0;
            $data['invoice_price'] ??= 0;
            $data['invoice_difference_percent'] ??= 0;
            $data['unit'] ??= $medicine->unit;
            $data['status'] ??= 'active';
        }

        return $this->calculate($data);
    }

    protected function exportRows(array $filters = []): Collection
    {
        return SupplierTracking::query()
            ->with('medicine')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('supplier_name', 'like', "%{$search}%")
                ->orWhere('supplier_representative', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%")
                ->orWhereHas('medicine', fn ($medicine) => $medicine
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        return [
            'Ngày làm việc' => $model->working_date?->format('d/m/Y'),
            'Tên thuốc' => $model->medicine?->name,
            'Số đăng ký' => $model->medicine?->registration_number,
            'Nhà cung cấp' => $model->supplier_name,
            'Người đại diện' => $model->supplier_representative,
            'Khu vực' => $model->area,
            'Giá nhập' => $model->import_price,
            'Giá bán' => $model->selling_price,
            'Giá hóa đơn' => $model->invoice_price,
            'Chênh lệch hóa đơn' => $model->invoice_difference_amount,
            '% phí chênh lệch' => $model->invoice_difference_percent,
            'Phí chênh lệch' => $model->invoice_difference_fee,
            'Giá vốn' => $model->cost_price,
            '% lợi nhuận thực tế' => $model->gross_profit_percent,
            'Số lượng cam kết' => $model->committed_quantity,
            'Đơn vị' => $model->unit,
            'Tiền cọc' => $model->deposit_amount,
            'Ngày bắt đầu' => $model->start_date?->format('d/m/Y'),
            'Ngày kết thúc' => $model->end_date?->format('d/m/Y'),
            'URL hợp đồng' => $model->contract_url,
            'Trạng thái' => $model->status,
            'Ghi chú' => $model->note,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'Ngày làm việc' => '01/05/2026',
            'Tên thuốc' => 'Trosicam 15mg',
            'Số đăng ký' => 'VN-20104-16',
            'Nhà cung cấp' => 'Công ty TNHH Dược Phẩm ABC',
            'Người đại diện' => 'Nguyễn Văn A',
            'Khu vực' => 'Miền Nam',
            'Giá nhập' => 3750,
            'Giá bán' => 7791,
            'Giá hóa đơn' => 7000,
            'Chênh lệch hóa đơn' => 'Hệ thống tự tính',
            '% phí chênh lệch' => 10,
            'Phí chênh lệch' => 'Hệ thống tự tính',
            'Giá vốn' => 'Hệ thống tự tính',
            '% lợi nhuận thực tế' => 'Hệ thống tự tính',
            'Số lượng cam kết' => 500000,
            'Đơn vị' => 'Viên',
            'Tiền cọc' => 50000000,
            'Ngày bắt đầu' => '01/06/2026',
            'Ngày kết thúc' => '01/06/2027',
            'URL hợp đồng' => null,
            'Trạng thái' => 'active',
            'Ghi chú' => null,
        ];
    }

    private function existingRecord(array $data): ?SupplierTracking
    {
        if (! $data['medicine_id'] || ! $data['supplier_name'] || ! $data['working_date']) {
            return null;
        }

        return SupplierTracking::query()->where([
            'medicine_id' => $data['medicine_id'],
            'supplier_name' => $data['supplier_name'],
            'working_date' => $data['working_date'],
        ])->first();
    }

    private function findMedicine(?string $registrationNumber, ?string $medicineName): ?Medicine
    {
        if ($registrationNumber) {
            $medicine = Medicine::query()
                ->whereRaw('LOWER(TRIM(registration_number)) = ?', [mb_strtolower($registrationNumber)])
                ->first();

            if ($medicine) {
                return $medicine;
            }
        }

        return $medicineName
            ? Medicine::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($medicineName)])->first()
            : null;
    }

    private function normalizeStatus(mixed $status): ?string
    {
        $status = mb_strtolower(trim((string) $status));

        if ($status === '') {
            return null;
        }

        return match ($status) {
            'active', 'đang theo dõi', 'dang theo doi' => 'active',
            'completed', 'hoàn tất', 'hoan tat' => 'completed',
            'paused', 'tạm dừng', 'tam dung' => 'paused',
            'cancelled', 'canceled', 'hủy', 'huy' => 'cancelled',
            default => $status,
        };
    }

    private function calculate(array $data): array
    {
        $importPrice = (float) $data['import_price'];
        $sellingPrice = (float) $data['selling_price'];
        $invoicePrice = (float) $data['invoice_price'];
        $differencePercent = (float) $data['invoice_difference_percent'];
        $differenceAmount = $invoicePrice - $importPrice;
        $differenceFee = $differenceAmount * $differencePercent / 100;
        $costPrice = $importPrice + $differenceFee;

        $data['invoice_difference_amount'] = round($differenceAmount, 2);
        $data['invoice_difference_fee'] = round($differenceFee, 2);
        $data['cost_price'] = round($costPrice, 2);
        $data['gross_profit_percent'] = round(
            $sellingPrice > 0 ? (($sellingPrice - $costPrice) / $sellingPrice) * 100 : 0,
            2
        );

        return $data;
    }

    private function vietnameseNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', '₫', 'đ'], '', trim((string) $value));
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

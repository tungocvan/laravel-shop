<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    private array $pendingFacilityIds = [];
    protected string $defaultSheetName = 'Theo_doi_nha_cung_cap';

    protected string $mode = 'update_or_create';

    protected bool $ignoreNullValuesOnUpdate = true;

    protected array $uniqueBy = ['medicine_id', 'partner_id', 'working_date'];

    protected array $rules = [
        'medicine_id' => ['required', 'integer', 'exists:pharma_medicines,id'],
        'working_date' => ['required', 'date'],
        'partner_id' => ['required', 'integer', 'exists:partners,id'],
        'supplier_name' => ['required', 'string', 'max:255'],
        'supplier_name_normalized' => ['required', 'string', 'max:255'],
        'distribution_scope' => ['required', 'in:all,regions,facilities'],
        'distribution_regions' => ['nullable', 'array'],
        'distribution_provinces' => ['nullable', 'array'],
        'supplier_representative' => ['nullable', 'string', 'max:255'],
        'area' => ['nullable', 'string', 'max:255'],
        'import_price' => ['required', 'numeric', 'min:0'],
        'selling_price' => ['nullable', 'numeric', 'min:0'],
        'invoice_price' => ['required', 'numeric', 'min:0'],
        'invoice_difference_amount' => ['required', 'numeric'],
        'invoice_difference_percent' => ['nullable', 'numeric', 'min:0'],
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

    public function allowedImportModes(): array
    {
        return ['create_only', 'update_or_create', 'skip_duplicate'];
    }

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
            'E' => 'supplier_tax_code',
            'F' => 'supplier_representative',
            'G' => 'area',
            'H' => 'distribution_scope',
            'I' => 'distribution_regions',
            'J' => 'distribution_provinces',
            'K' => 'facility_codes',
            'L' => 'import_price',
            'M' => 'invoice_price',
            'N' => 'committed_quantity',
            'O' => 'unit',
            'P' => 'deposit_amount',
            'Q' => 'start_date',
            'R' => 'end_date',
            'S' => 'contract_url',
            'T' => 'status',
            'U' => 'note',
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

        $partner = $this->findSupplier(
            $this->cleanString($row['supplier_tax_code'] ?? null),
            $this->cleanString($row['supplier_name'] ?? null)
        );

        if (! $partner) {
            throw new \RuntimeException('Không tìm thấy nhà cung cấp theo mã số thuế hoặc tên nhà cung cấp.');
        }

        $scope = $this->normalizeDistributionScope($row['distribution_scope'] ?? null);
        $regions = $scope === 'regions' ? $this->stringList($row['distribution_regions'] ?? null) : null;
        $provinces = $scope === 'regions' ? $this->stringList($row['distribution_provinces'] ?? null) : null;
        $facilityIds = $scope === 'facilities'
            ? $this->facilityIdsFromCodes($this->stringList($row['facility_codes'] ?? null))
            : [];

        $data = [
            'medicine_id' => $medicine->id,
            'partner_id' => $partner->id,
            'working_date' => $this->cleanDate($row['working_date'] ?? null),
            'supplier_name' => $partner->name,
            'supplier_name_normalized' => Str::of($partner->name)->trim()->squish()->lower()->toString(),
            'supplier_representative' => $partner->contact_person ?: $this->cleanString($row['supplier_representative'] ?? null),
            'area' => $this->cleanString($row['area'] ?? null),
            'distribution_scope' => $scope,
            'distribution_regions' => $regions,
            'distribution_provinces' => $provinces,
            'facility_ids' => $facilityIds,
            'import_price' => $this->vietnameseNumber($row['import_price'] ?? null),
            'selling_price' => 0,
            'invoice_price' => $this->vietnameseNumber($row['invoice_price'] ?? null),
            'invoice_difference_percent' => 0,
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
            foreach (['selling_price', 'invoice_difference_percent'] as $field) {
                $data[$field] = $existing->getAttribute($field) ?? $data[$field];
            }
            foreach ($data as $field => $value) {
                if ($value === null && ! in_array($field, ['distribution_regions', 'distribution_provinces'], true)) {
                    $data[$field] = $existing->getAttribute($field);
                }
            }
        } else {
            $data['import_price'] ??= 0;
            $data['invoice_price'] ??= 0;
            $data['unit'] ??= $medicine->unit;
            $data['status'] ??= 'active';
        }

        return $this->calculate($data);
    }

    protected function beforePersist(array $data, array $row, int $rowNumber, string $sheet): array
    {
        $facilityIds = $data['facility_ids'] ?? [];
        unset($data['facility_ids']);

        $this->pendingFacilityIds[$this->businessKey($data)] = $facilityIds;

        return $data;
    }

    protected function persistRow(array $data, string $mode): Model
    {
        $model = parent::persistRow($data, $mode);
        $facilityIds = $this->pendingFacilityIds[$this->businessKey($data)] ?? [];
        $model->facilities()->sync($facilityIds);
        unset($this->pendingFacilityIds[$this->businessKey($data)]);

        return $model;
    }

    protected function exportRows(array $filters = []): Collection
    {
        $selectedIds = $this->selectedIds($filters);

        if ($selectedIds !== []) {
            return SupplierTracking::query()
                ->with(['medicine', 'partner', 'facilities'])
                ->whereKey($selectedIds)
                ->latest('id')
                ->get();
        }

        return SupplierTracking::query()
            ->with(['medicine', 'partner', 'facilities'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('supplier_name', 'like', "%{$search}%")
                ->orWhere('supplier_representative', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%")
                ->orWhereHas('medicine', fn ($medicine) => $medicine
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['working_date_from'] ?? null, fn ($query, $date) => $query->whereDate('working_date', '>=', $date))
            ->when($filters['working_date_to'] ?? null, fn ($query, $date) => $query->whereDate('working_date', '<=', $date))
            ->latest('id')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        return [
            'Ngày làm việc' => $model->working_date?->format('d/m/Y'),
            'Tên thuốc' => $model->medicine?->name,
            'Số đăng ký' => $model->medicine?->registration_number,
            'Nhà cung cấp' => $model->partner?->name ?? $model->supplier_name,
            'Mã số thuế NCC' => $model->partner?->tax_code,
            'Người đại diện' => $model->partner?->contact_person ?? $model->supplier_representative,
            'Khu vực' => $model->area,
            'Phạm vi phân phối' => $model->distribution_scope ?: 'all',
            'Vùng miền' => $this->joinList($model->distribution_regions),
            'Tỉnh/Thành' => $this->joinList($model->distribution_provinces),
            'Mã cơ sở' => $model->facilities->pluck('external_id')->filter()->implode('; '),
            'Giá vốn NCC' => $model->import_price,
            'Giá hóa đơn NCC' => $model->invoice_price,
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
            'Mã số thuế NCC' => '0312345678',
            'Người đại diện' => 'Nguyễn Văn A',
            'Khu vực' => 'Miền Nam',
            'Phạm vi phân phối' => 'all',
            'Vùng miền' => null,
            'Tỉnh/Thành' => null,
            'Mã cơ sở' => null,
            'Giá vốn NCC' => 3750,
            'Giá hóa đơn NCC' => 7000,
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
        if (! $data['medicine_id'] || ! $data['partner_id'] || ! $data['working_date']) {
            return null;
        }

        return SupplierTracking::query()->where([
            'medicine_id' => $data['medicine_id'],
            'partner_id' => $data['partner_id'],
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

    private function findSupplier(?string $taxCode, ?string $supplierName): ?Partner
    {
        if ($taxCode) {
            $partner = Partner::query()
                ->withPartnerType('supplier')
                ->whereRaw('LOWER(TRIM(tax_code)) = ?', [mb_strtolower($taxCode)])
                ->first();

            if ($partner) {
                return $partner;
            }
        }

        return $supplierName
            ? Partner::query()->withPartnerType('supplier')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($supplierName)])
                ->first()
            : null;
    }

    private function normalizeDistributionScope(mixed $scope): string
    {
        $scope = mb_strtolower(trim((string) $scope));

        return match ($scope) {
            'regions', 'region', 'vùng miền', 'vung mien' => 'regions',
            'facilities', 'facility', 'cơ sở', 'co so' => 'facilities',
            default => 'all',
        };
    }

    private function stringList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($item) => $item !== ''));
        }

        return collect(preg_split('/[;,\\n]+/u', trim((string) $value)) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function facilityIdsFromCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return OfficialSourceFacility::query()
            ->whereIn('external_id', $codes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function joinList(mixed $value): string
    {
        return collect($value ?? [])->filter()->implode('; ');
    }

    private function businessKey(array $data): string
    {
        return implode('|', [
            (string) ($data['medicine_id'] ?? ''),
            (string) ($data['partner_id'] ?? ''),
            (string) ($data['working_date'] ?? ''),
        ]);
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

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', '₫', 'đ'], '', trim((string) $value));
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

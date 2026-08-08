<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Pharma\Models\Medicine;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class MedicineImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'Mau_Ho_So_SanPham';

    protected string $mode = 'update_or_create';

    protected bool $ignoreNullValuesOnUpdate = true;

    protected array $uniqueBy = [
        'registration_number',
        'packaging_specification',
    ];

    protected array $rules = [
        'circular_order_number' => ['nullable', 'string', 'max:255'],
        'circular_group' => ['nullable', 'string', 'max:255'],
        'active_ingredients' => ['required', 'string', 'max:255'],
        'concentration' => ['required', 'string', 'max:255'],
        'name' => ['required', 'string', 'max:255'],
        'dosage_form' => ['required', 'string', 'max:255'],
        'route_of_administration' => ['required', 'string', 'max:255'],
        'unit' => ['required', 'string', 'max:255'],
        'packaging_specification' => ['required', 'string', 'max:255'],
        'registration_number' => ['required', 'string', 'max:255'],
        'shelf_life' => ['required', 'string', 'max:255'],
        'registered_company' => ['required', 'string', 'max:255'],
        'manufacturing_company' => ['required', 'string', 'max:255'],
        'manufacturing_country' => ['required', 'string', 'max:255'],
        'visa_validity_date' => ['nullable', 'date'],
        'gmp_certification_date' => ['nullable', 'date'],
        'declared_price' => ['nullable', 'numeric', 'min:0'],
        'profile_link' => ['nullable', 'url'],
        'is_special_control' => ['required', 'boolean'],
        'notes' => ['nullable', 'string'],
    ];

    protected function modelClass(): string
    {
        return Medicine::class;
    }

    public function columnMapping(): array
    {
        return [
            'B' => 'circular_order_number',
            'C' => 'circular_group',
            'D' => 'active_ingredients',
            'E' => 'concentration',
            'F' => 'name',
            'G' => 'dosage_form',
            'H' => 'route_of_administration',
            'I' => 'unit',
            'J' => 'packaging_specification',
            'K' => 'registration_number',
            'L' => 'shelf_life',
            'M' => 'registered_company',
            'N' => 'manufacturing_company',
            'O' => 'manufacturing_country',
            'P' => 'visa_validity_date',
            'Q' => 'gmp_certification_date',
            'R' => 'declared_price',
            'S' => 'profile_link',
            'T' => 'is_special_control',
            'U' => 'notes',
        ];
    }

    protected function normalizeRow(array $row): array
    {
        $data = [
            'circular_order_number' => $this->cleanString($row['circular_order_number'] ?? null),
            'circular_group' => $this->cleanString($row['circular_group'] ?? null),
            'active_ingredients' => $this->cleanString($row['active_ingredients'] ?? null),
            'concentration' => $this->cleanString($row['concentration'] ?? null),
            'name' => $this->cleanString($row['name'] ?? null),
            'dosage_form' => $this->cleanString($row['dosage_form'] ?? null),
            'route_of_administration' => $this->cleanString($row['route_of_administration'] ?? null),
            'unit' => $this->cleanString($row['unit'] ?? null),
            'packaging_specification' => $this->cleanString($row['packaging_specification'] ?? null),
            'registration_number' => $this->cleanString($row['registration_number'] ?? null),
            'shelf_life' => $this->cleanString($row['shelf_life'] ?? null),
            'registered_company' => $this->cleanString($row['registered_company'] ?? null),
            'manufacturing_company' => $this->cleanString($row['manufacturing_company'] ?? null),
            'manufacturing_country' => $this->cleanString($row['manufacturing_country'] ?? null),
            'visa_validity_date' => $this->cleanDate($row['visa_validity_date'] ?? null),
            'gmp_certification_date' => $this->cleanDate($row['gmp_certification_date'] ?? null),
            'declared_price' => $this->vietnameseNumber($row['declared_price'] ?? null),
            'profile_link' => $this->cleanString($row['profile_link'] ?? null),
            'is_special_control' => $this->cleanBoolean($row['is_special_control'] ?? null),
            'notes' => $this->cleanString($row['notes'] ?? null),
        ];

        $existing = $this->existingRecord($data);

        if ($existing) {
            foreach ($data as $field => $value) {
                if ($value === null) {
                    $data[$field] = $existing->getAttribute($field);
                }
            }
        } elseif ($data['is_special_control'] === null) {
            $data['is_special_control'] = false;
        }

        return $data;
    }

    protected function exportRows(array $filters = []): Collection
    {
        return Medicine::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($nested) => $nested
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('active_ingredients', 'like', "%{$search}%")))
            ->when($filters['circular_group'] ?? null, fn ($query, $group) => $query->where('circular_group', $group))
            ->when(isset($filters['is_special_control']), fn ($query) => $query->where('is_special_control', $filters['is_special_control']))
            ->latest('id')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        return [
            'Số thứ tự theo thông tư' => $model->circular_order_number,
            'Phân nhóm theo thông tư' => $model->circular_group,
            'Tên hoạt chất' => $model->active_ingredients,
            'Nồng độ - Hàm lượng' => $model->concentration,
            'Tên thuốc' => $model->name,
            'Dạng bào chế' => $model->dosage_form,
            'Đường dùng' => $model->route_of_administration,
            'Đơn vị tính' => $model->unit,
            'Quy cách đóng gói' => $model->packaging_specification,
            'Giấy phép lưu hành sản phẩm' => $model->registration_number,
            'Hạn dùng' => $model->shelf_life,
            'Cơ sở đăng ký' => $model->registered_company,
            'Cơ sở sản xuất' => $model->manufacturing_company,
            'Nước sản xuất' => $model->manufacturing_country,
            'Hiệu lực Visa' => $model->visa_validity_date?->format('d/m/Y'),
            'GMP Cơ sở sản xuất' => $model->gmp_certification_date?->format('d/m/Y'),
            'Giá kê khai' => $model->declared_price,
            'Link Hồ sơ sản phẩm' => $model->profile_link,
            'Hoạt chất kiểm soát đặc biệt' => $model->is_special_control ? 'Có' : 'Không',
            'Ghi chú' => $model->notes,
        ];
    }

    protected function templateSampleRow(): array
    {
        return ['STT' => 1] + $this->mapExportRow(new Medicine([
            'circular_order_number' => '48', 'circular_group' => '1', 'active_ingredients' => 'Meloxicam',
            'concentration' => '15mg', 'name' => 'Trosicam 15mg', 'dosage_form' => 'Viên hòa tan nhanh',
            'route_of_administration' => 'Uống', 'unit' => 'Viên', 'packaging_specification' => 'Hộp 3 vỉ x 10 viên',
            'registration_number' => 'VN-20104-16', 'shelf_life' => '36 tháng',
            'registered_company' => 'Alpex Pharma SA, Thụy Sĩ', 'manufacturing_company' => 'Alpex Pharma SA',
            'manufacturing_country' => 'Thụy Sĩ', 'declared_price' => 8500, 'is_special_control' => false,
        ]));
    }

    private function existingRecord(array $data): ?Medicine
    {
        if (! $data['registration_number'] || ! $data['packaging_specification']) {
            return null;
        }

        return Medicine::query()->where([
            'registration_number' => $data['registration_number'],
            'packaging_specification' => $data['packaging_specification'],
        ])->first();
    }

    private function vietnameseNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $normalized = str_replace([',', '.', ' ', '₫', 'đ'], '', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

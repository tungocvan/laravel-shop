<?php

namespace Modules\Admission\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Admission\Models\AdmissionLocation;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'don_vi_hanh_chinh';

    protected array $requiredHeaders = [
        'province_code',
        'province_name',
        'ward_code',
        'ward_name',
    ];

    protected array $uniqueBy = ['ward_code'];

    protected array $rules = [
        'province_code' => ['required', 'string', 'max:255'],
        'province_name' => ['required', 'string', 'max:255'],
        'ward_code' => ['required', 'string', 'max:255'],
        'ward_name' => ['required', 'string', 'max:255'],
    ];

    protected array $headerAliases = [
        'province_code' => ['province_code', 'ma_tinh', 'mã tỉnh', 'mã tỉnh (bnv)', 'ma_tinh_bnv'],
        'province_name' => ['province_name', 'ten_tinh', 'tên tỉnh', 'tên tỉnh/tp mới', 'ten_tinh_tp_moi'],
        'ward_code' => ['ward_code', 'ma_phuong', 'mã phường', 'mã phường/xã mới', 'ma_phuong_xa_moi'],
        'ward_name' => ['ward_name', 'ten_phuong', 'tên phường', 'tên phường/xã mới', 'ten_phuong_xa_moi'],
    ];

    protected function modelClass(): string
    {
        return AdmissionLocation::class;
    }

    protected function columnMapping(): array
    {
        return [
            'B' => 'province_code',
            'C' => 'province_name',
            'F' => 'ward_code',
            'G' => 'ward_name',
        ];
    }

    protected function shouldUseColumnMapping(array $rawRow): bool
    {
        $mappedHeaders = $this->normalizeRowHeaders($rawRow);

        return collect($this->requiredHeaders)
            ->contains(fn (string $field): bool => ! array_key_exists($field, $mappedHeaders));
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'province_code' => $this->cleanString($row['province_code'] ?? null),
            'province_name' => $this->cleanString($row['province_name'] ?? null),
            'ward_code' => $this->cleanString($row['ward_code'] ?? null),
            'ward_name' => $this->cleanString($row['ward_name'] ?? null),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return AdmissionLocation::query()
            ->when($filters['province_name'] ?? null, fn ($query, $province) => $query->where('province_name', $province))
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('ward_name', 'like', "%{$search}%")
                        ->orWhere('ward_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('province_name')
            ->orderBy('ward_name')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        return [
            'province_code' => (string) $model->province_code,
            'province_name' => $model->province_name,
            'ward_code' => (string) $model->ward_code,
            'ward_name' => $model->ward_name,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'province_code' => '1',
            'province_name' => 'Thành phố Hà Nội',
            'ward_code' => '10105001',
            'ward_name' => 'Phường Hoàn Kiếm',
        ];
    }
}

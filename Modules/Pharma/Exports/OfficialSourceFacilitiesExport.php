<?php

namespace Modules\Pharma\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfficialSourceFacilitiesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    public function __construct(
        private readonly Collection $facilities,
        private readonly BhxhProvinceCatalog $provinceCatalog,
        private readonly array $partitionLabels,
    ) {}

    public function headings(): array
    {
        return [
            'Source',
            'External ID',
            'Nguồn',
            'Mã CSKCB',
            'Tên cơ sở',
            'Vùng miền ERP',
            'Tỉnh/Thành',
            'Vùng nguồn BHXH',
            'Mã vùng nguồn',
            'Địa bàn BHXH',
            'Mã địa bàn BHXH',
            'Trạng thái',
            'Lần đồng bộ cuối',
            'Source Province Code',
            'Source District Code',
        ];
    }

    public function collection(): Collection
    {
        return $this->facilities->map(fn ($facility): array => [
            (string) $facility->source,
            (string) $facility->external_id,
            strtoupper((string) $facility->source),
            (string) $facility->external_id,
            (string) $facility->facility_name,
            $this->provinceCatalog->regionForProvinceName((string) $facility->province_name) ?? '',
            (string) ($facility->province_name ?? ''),
            $this->partitionLabels[$facility->source_province_code] ?? (string) $facility->source_province_code,
            (string) $facility->source_province_code,
            (string) ($facility->district_name ?? ''),
            (string) ($facility->source_district_code ?? ''),
            $facility->is_active ? 'ACTIVE' : 'STALE',
            optional($facility->last_synced_at)->format('d/m/Y H:i') ?? '',
            (string) $facility->source_province_code,
            (string) ($facility->source_district_code ?? ''),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace Modules\Pharma\Imports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityNormalizer;
use RuntimeException;

class OfficialSourceFacilitiesImport implements SkipsUnknownSheets, ToArray, WithMultipleSheets
{
    public array $summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0];

    public function __construct(private readonly OfficialFacilityNormalizer $normalizer) {}

    public function sheets(): array
    {
        return [
            'Worksheet' => $this,
        ];
    }

    public function onUnknownSheet($sheetName): void
    {
        // Export workbooks may contain helper/reference sheets. They are intentionally ignored.
    }

    public function array(array $rows): void
    {
        if ($rows === []) {
            throw new RuntimeException('File Excel không có dữ liệu.');
        }

        $headings = array_map(fn ($value) => trim((string) $value), array_shift($rows));
        $required = ['Source', 'External ID', 'Tên cơ sở', 'Tỉnh/Thành', 'Source Province Code', 'Source District Code'];
        foreach ($required as $heading) {
            if (! in_array($heading, $headings, true)) {
                throw new RuntimeException("File không đúng mẫu export: thiếu cột {$heading}.");
            }
        }

        $indexes = array_flip($headings);

        DB::transaction(function () use ($rows, $indexes): void {
            foreach ($rows as $row) {
                $source = strtolower(trim((string) ($row[$indexes['Source']] ?? '')));
                $externalId = trim((string) ($row[$indexes['External ID']] ?? ''));
                $facilityName = $this->normalizer->text($row[$indexes['Tên cơ sở']] ?? null);
                $provinceName = $this->normalizer->text($row[$indexes['Tỉnh/Thành']] ?? null);
                $sourceProvinceCode = trim((string) ($row[$indexes['Source Province Code']] ?? ''));
                $sourceDistrictCode = trim((string) ($row[$indexes['Source District Code']] ?? ''));
                $districtName = isset($indexes['Địa bàn BHXH'])
                    ? $this->normalizer->text($row[$indexes['Địa bàn BHXH']] ?? null)
                    : null;
                $active = ! isset($indexes['Trạng thái']) || strtoupper(trim((string) ($row[$indexes['Trạng thái']] ?? 'ACTIVE'))) === 'ACTIVE';

                if ($source === '' && $externalId === '' && $facilityName === null) {
                    continue;
                }

                if ($source === '' || $externalId === '' || $facilityName === null || $provinceName === null || $sourceProvinceCode === '') {
                    $this->summary['errors']++;
                    continue;
                }

                $payload = [
                    'external_id' => $externalId,
                    'facility_name' => $facilityName,
                    'source_province_code' => $sourceProvinceCode,
                    'province_name' => $provinceName,
                    'source_district_code' => $sourceDistrictCode !== '' ? $sourceDistrictCode : null,
                    'district_name' => $districtName,
                ];
                $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $record = OfficialSourceFacility::query()
                    ->where('source', $source)
                    ->where('external_id', $externalId)
                    ->lockForUpdate()
                    ->first();

                $attributes = [
                    'facility_name' => $facilityName,
                    'normalized_name' => $this->normalizer->identity($facilityName),
                    'source_province_code' => $sourceProvinceCode,
                    'province_name' => $provinceName,
                    'source_district_code' => $sourceDistrictCode !== '' ? $sourceDistrictCode : null,
                    'district_name' => $districtName,
                    'raw_payload' => $payload,
                    'payload_hash' => $hash,
                    'is_active' => $active,
                    'last_seen_at' => now(),
                    'last_synced_at' => now(),
                ];

                if ($record === null) {
                    OfficialSourceFacility::query()->create($attributes + [
                        'source' => $source,
                        'external_id' => $externalId,
                        'first_seen_at' => now(),
                    ]);
                    $this->summary['created']++;

                    continue;
                }

                $changed = $record->payload_hash !== $hash || $record->is_active !== $active;
                if ($changed) {
                    $record->update($attributes);
                    $this->summary['updated']++;
                } else {
                    $this->summary['unchanged']++;
                }
            }
        });
    }
}

<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
use Modules\Pharma\Models\OfficialFacilityImportRow;
use RuntimeException;

class OfficialFacilityPartnerImporter
{
    public function import(OfficialFacilityImportRow $row): Partner
    {
        if (! $row->is_selected) {
            throw new RuntimeException('Chỉ được import dòng đã chọn.');
        }
        if ($row->classification === 'INVALID') {
            throw new RuntimeException('Dòng INVALID không được import.');
        }
        if (in_array($row->classification, ['LIKELY_MATCH', 'CONFLICT'], true)
            && ! in_array($row->resolution_status, ['LINKED', 'CREATE_NEW'], true)) {
            throw new RuntimeException('Dòng cần được xử lý thủ công trước khi import.');
        }

        return DB::transaction(function () use ($row) {
            $row->refresh();
            $batch = $row->batch()->lockForUpdate()->firstOrFail();
            $reference = null;

            if ($row->external_id) {
                $reference = PartnerSourceReference::query()
                    ->where('source', $batch->source)
                    ->where('external_id', $row->external_id)
                    ->lockForUpdate()
                    ->first();
            }

            $partner = $reference?->partner;
            if (! $partner && $row->resolution_status === 'LINKED' && $row->resolved_partner_id) {
                $partner = Partner::query()->lockForUpdate()->findOrFail($row->resolved_partner_id);
            }
            if (! $partner && $row->classification === 'EXACT' && $row->matched_partner_id) {
                $partner = Partner::query()->lockForUpdate()->findOrFail($row->matched_partner_id);
            }

            if (! $partner) {
                $partner = Partner::query()->create([
                    'name' => $row->facility_name,
                    'tax_code' => $row->tax_code,
                    'legal_type' => 'hospital',
                    'partner_types' => ['customer'],
                    'address' => $row->address,
                    'province_code' => $row->province_code,
                    'source' => 'import',
                    'status' => 'active',
                ]);
            } else {
                if ($partner->tax_code && $row->tax_code && $partner->tax_code !== $row->tax_code) {
                    throw new RuntimeException('Xung đột mã số thuế; yêu cầu xử lý thủ công.');
                }
                if ($partner->province_code && $row->province_code && $partner->province_code !== $row->province_code) {
                    throw new RuntimeException('Xung đột tỉnh canonical; yêu cầu xử lý thủ công.');
                }

                $safe = [];
                if (! $partner->tax_code && $row->tax_code) {
                    $safe['tax_code'] = $row->tax_code;
                }
                if (! $partner->province_code && $row->province_code) {
                    $safe['province_code'] = $row->province_code;
                }
                if (! $partner->address && $row->address) {
                    $safe['address'] = $row->address;
                }
                if ($safe !== []) {
                    $partner->update($safe);
                }
            }

            if ($row->external_id) {
                try {
                    PartnerSourceReference::query()->updateOrCreate(
                        ['source' => $batch->source, 'external_id' => $row->external_id],
                        [
                            'partner_id' => $partner->id,
                            'source_province_code' => $row->source_province_code,
                            'source_date' => $batch->source_date,
                            'last_seen_at' => now(),
                            'first_seen_at' => $reference?->first_seen_at ?? now(),
                            'metadata' => ['official_facility_import_batch_id' => $batch->id],
                        ]
                    );
                } catch (QueryException $exception) {
                    $existing = PartnerSourceReference::query()
                        ->where('source', $batch->source)
                        ->where('external_id', $row->external_id)
                        ->first();
                    if (! $existing) {
                        throw $exception;
                    }
                    $partner = $existing->partner;
                    $existing->update(['last_seen_at' => now(), 'source_date' => $batch->source_date]);
                }
            }

            $row->update([
                'import_status' => $partner->wasRecentlyCreated ? 'CREATED' : 'LINKED',
                'imported_partner_id' => $partner->id,
                'imported_at' => now(),
                'error_message' => null,
            ]);

            return $partner;
        }, 3);
    }
}

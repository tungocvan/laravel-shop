<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceSyncBatch;

class OfficialSourceMirrorService
{
    public function __construct(private readonly OfficialFacilityNormalizer $normalizer)
    {
    }

    public function persist(OfficialSourceSyncBatch $batch, array $facilities): void
    {
        DB::transaction(function () use ($batch, $facilities): void {
            $batch->update([
                'status' => 'RUNNING',
                'started_at' => $batch->started_at ?? now(),
                'fetched_count' => count($facilities),
                'error_message' => null,
            ]);

            $created = 0;
            $updated = 0;
            $unchanged = 0;
            $seenIds = [];

            foreach ($facilities as $facility) {
                $externalId = trim((string) ($facility['external_id'] ?? ''));
                $facilityName = $this->normalizer->text($facility['facility_name'] ?? null);

                if ($externalId === '' || $facilityName === null) {
                    continue;
                }

                $seenIds[] = $externalId;
                $rawPayload = [
                    'external_id' => $externalId,
                    'facility_name' => $facilityName,
                    'source_province_code' => $batch->source_province_code,
                    'province_name' => $batch->province_name,
                    'source_district_code' => $batch->source_district_code,
                    'district_name' => $batch->district_name,
                ];
                $hash = hash('sha256', json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $record = OfficialSourceFacility::query()
                    ->where('source', $batch->source)
                    ->where('external_id', $externalId)
                    ->lockForUpdate()
                    ->first();

                if ($record === null) {
                    OfficialSourceFacility::query()->create([
                        'source' => $batch->source,
                        'external_id' => $externalId,
                        'facility_name' => $facilityName,
                        'normalized_name' => $this->normalizer->identity($facilityName),
                        'source_province_code' => $batch->source_province_code,
                        'province_name' => $batch->province_name,
                        'source_district_code' => $batch->source_district_code,
                        'district_name' => $batch->district_name,
                        'raw_payload' => $rawPayload,
                        'payload_hash' => $hash,
                        'is_active' => true,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                        'last_synced_at' => now(),
                        'last_sync_batch_id' => $batch->id,
                    ]);
                    $created++;
                    continue;
                }

                $changed = $record->payload_hash !== $hash || ! $record->is_active;
                $record->update([
                    'facility_name' => $facilityName,
                    'normalized_name' => $this->normalizer->identity($facilityName),
                    'source_province_code' => $batch->source_province_code,
                    'province_name' => $batch->province_name,
                    'source_district_code' => $batch->source_district_code,
                    'district_name' => $batch->district_name,
                    'raw_payload' => $rawPayload,
                    'payload_hash' => $hash,
                    'is_active' => true,
                    'last_seen_at' => now(),
                    'last_synced_at' => now(),
                    'last_sync_batch_id' => $batch->id,
                ]);

                $changed ? $updated++ : $unchanged++;
            }

            $stale = 0;
            if ($batch->sync_scope === 'province') {
                $staleQuery = OfficialSourceFacility::query()
                    ->where('source', $batch->source)
                    ->where('source_province_code', $batch->source_province_code)
                    ->where('is_active', true);

                if ($seenIds !== []) {
                    $staleQuery->whereNotIn('external_id', $seenIds);
                }

                $stale = $staleQuery->update([
                    'is_active' => false,
                    'last_synced_at' => now(),
                    'last_sync_batch_id' => $batch->id,
                ]);
            }

            $batch->update([
                'status' => 'COMPLETED',
                'created_count' => $created,
                'updated_count' => $updated,
                'unchanged_count' => $unchanged,
                'stale_count' => $stale,
                'completed_at' => now(),
            ]);
        });
    }
}

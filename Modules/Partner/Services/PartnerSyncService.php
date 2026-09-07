<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;

class PartnerSyncService
{
    public function sync(?Partner $partner, ExternalPartnerData $external, array $selectedFields): Partner
    {
        return DB::transaction(function () use ($partner, $external, $selectedFields): Partner {
            $existingReference = PartnerSourceReference::query()
                ->where('source', $external->source)
                ->where('external_id', $external->externalId)
                ->lockForUpdate()
                ->first();

            if ($existingReference && $partner && $existingReference->partner_id !== $partner->id) {
                throw ValidationException::withMessages([
                    'sync' => 'Nguồn dữ liệu này đã được liên kết với một Partner khác.',
                ]);
            }

            if ($existingReference && ! $partner) {
                $partner = $existingReference->partner;
            }

            $allowed = [
                'tax_code' => $external->taxCode,
                'name' => $external->name,
                'address' => $external->address,
            ];

            $changes = [];
            foreach ($selectedFields as $field) {
                if (array_key_exists($field, $allowed) && $allowed[$field] !== null && $allowed[$field] !== '') {
                    $changes[$field] = $allowed[$field];
                }
            }

            if (! $partner) {
                $duplicate = $external->taxCode ? Partner::where('tax_code', $external->taxCode)->first() : null;
                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'sync' => 'Đã tồn tại Partner có cùng mã số thuế. Hãy chọn Partner hiện có để đồng bộ.',
                    ]);
                }

                $partner = Partner::create(array_merge([
                    'name' => $external->name ?: $external->taxCode ?: $external->externalId,
                    'legal_type' => 'company',
                    'partner_types' => ['supplier'],
                    'source' => 'system',
                    'status' => 'active',
                ], $changes));
            } elseif ($changes !== []) {
                $partner->update($changes);
            }

            $snapshot = $external->snapshot();
            $now = now();
            $metadata = array_filter([
                'source_url' => $external->sourceUrl,
                'last_lookup_at' => $external->checkedAt,
                'last_synced_at' => $now->toIso8601String(),
                'match_type' => $external->matchType,
                'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ], fn ($value) => $value !== null);

            $reference = PartnerSourceReference::firstOrNew([
                'source' => $external->source,
                'external_id' => $external->externalId,
            ]);

            if ($reference->exists && $reference->partner_id !== $partner->id) {
                throw ValidationException::withMessages([
                    'sync' => 'Nguồn dữ liệu này đã được liên kết với một Partner khác.',
                ]);
            }

            $reference->partner_id = $partner->id;
            $reference->first_seen_at ??= $now;
            $reference->last_seen_at = $now;
            $reference->metadata = array_merge($reference->metadata ?? [], $metadata);
            $reference->save();

            return $partner->fresh();
        });
    }
}

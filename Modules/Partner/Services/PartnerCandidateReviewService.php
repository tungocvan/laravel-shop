<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\DB;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
use Modules\Partner\Models\PartnerSyncCandidate;

class PartnerCandidateReviewService
{
    public function createPartner(PartnerSyncCandidate $candidate): Partner
    {
        return DB::transaction(function () use ($candidate): Partner {
            $candidate = PartnerSyncCandidate::query()->lockForUpdate()->findOrFail($candidate->getKey());

            if ($candidate->matched_partner_id) {
                throw new \RuntimeException('Candidate đã được liên kết với Partner hiện hữu.');
            }

            $existing = Partner::query()->where('tax_code', $candidate->tax_code)->first();
            if ($existing) {
                throw new \RuntimeException('Mã số thuế đã tồn tại trong Partner. Hãy dùng chức năng liên kết/merge.');
            }

            $partner = Partner::query()->create([
                'tax_code' => $candidate->tax_code,
                'name' => $candidate->name ?: $candidate->tax_code,
                'legal_type' => 'company',
                'partner_types' => $candidate->partner_types ?: [],
                'phone' => $candidate->phone,
                'email' => $candidate->email,
                'address' => $candidate->address,
                'source' => 'system',
                'status' => 'active',
            ]);

            $this->finalizeCandidate($candidate, $partner, []);

            return $partner;
        });
    }

    public function confirmExisting(PartnerSyncCandidate $candidate, array $selectedFields = []): Partner
    {
        return DB::transaction(function () use ($candidate, $selectedFields): Partner {
            $candidate = PartnerSyncCandidate::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $partner = $candidate->matched_partner_id
                ? Partner::query()->lockForUpdate()->find($candidate->matched_partner_id)
                : Partner::query()->lockForUpdate()->where('tax_code', $candidate->tax_code)->first();

            if (! $partner) {
                throw new \RuntimeException('Không tìm thấy Partner để liên kết candidate.');
            }

            $allowed = ['name', 'address', 'email', 'phone', 'partner_types'];
            $selectedFields = collect($selectedFields)
                ->filter(fn ($field) => in_array($field, $allowed, true))
                ->unique()
                ->values()
                ->all();

            $updates = [];
            foreach (['name', 'address', 'email', 'phone'] as $field) {
                if (in_array($field, $selectedFields, true) && filled($candidate->{$field})) {
                    $updates[$field] = $candidate->{$field};
                }
            }

            if (in_array('partner_types', $selectedFields, true)) {
                $updates['partner_types'] = collect($partner->partner_types ?? [])
                    ->merge($candidate->partner_types ?? [])
                    ->filter(fn ($type) => in_array($type, ['customer', 'supplier'], true))
                    ->unique()
                    ->values()
                    ->all();
            }

            if ($updates !== []) {
                $partner->fill($updates)->save();
            }

            $this->finalizeCandidate($candidate, $partner, $selectedFields);

            return $partner->refresh();
        });
    }

    public function ignore(PartnerSyncCandidate $candidate): PartnerSyncCandidate
    {
        $candidate->forceFill([
            'status' => 'ignored',
            'last_seen_at' => now(),
        ])->save();

        return $candidate->refresh();
    }

    private function finalizeCandidate(PartnerSyncCandidate $candidate, Partner $partner, array $selectedFields): void
    {
        $metadata = $candidate->source_metadata ?? [];
        if ($candidate->conflict_fields) {
            $metadata['reviewed_conflicts'] = $candidate->conflict_fields;
        }
        $metadata['reviewed_at'] = now()->toIso8601String();
        $metadata['applied_fields'] = array_values($selectedFields);

        $reference = PartnerSourceReference::query()
            ->where('source', $candidate->source)
            ->where('external_id', $candidate->tax_code)
            ->lockForUpdate()
            ->first();

        if ($reference && (int) $reference->partner_id !== (int) $partner->getKey()) {
            throw new \RuntimeException('Nguồn dữ liệu này đã được gắn với Partner khác; hệ thống không tự chuyển ownership.');
        }

        $candidate->forceFill([
            'matched_partner_id' => $partner->getKey(),
            'status' => 'matched',
            'conflict_fields' => null,
            'source_metadata' => $metadata,
            'last_seen_at' => now(),
        ])->save();

        $reference ??= new PartnerSourceReference([
            'partner_id' => $partner->getKey(),
            'source' => $candidate->source,
            'external_id' => $candidate->tax_code,
            'first_seen_at' => $candidate->first_seen_at ?? now(),
        ]);

        $reference->fill([
            'last_seen_at' => now(),
            'metadata' => [
                'candidate_id' => $candidate->getKey(),
                'partner_types' => $candidate->partner_types,
                'source_metadata' => $metadata,
            ],
        ])->save();
    }
}

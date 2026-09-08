<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSyncCandidate;

class PartnerCandidateIntakeService
{
    /**
     * Accept normalized candidate payloads from source modules without mutating Partner master data.
     *
     * @return array{total:int,pending:int,matched:int,conflict:int,ignored:int}
     */
    public function intake(string $source, array $candidates): array
    {
        $source = trim($source);
        if ($source === '') {
            throw new \InvalidArgumentException('Nguồn candidate không được để trống.');
        }

        $summary = [
            'total' => 0,
            'pending' => 0,
            'matched' => 0,
            'conflict' => 0,
            'ignored' => 0,
        ];

        DB::transaction(function () use ($source, $candidates, &$summary): void {
            foreach ($candidates as $candidate) {
                $taxCode = $this->clean($candidate['tax_code'] ?? null);
                if ($taxCode === null) {
                    continue;
                }

                $existingCandidate = PartnerSyncCandidate::query()
                    ->where('source', $source)
                    ->where('tax_code', $taxCode)
                    ->lockForUpdate()
                    ->first();

                $partner = Partner::query()->where('tax_code', $taxCode)->first();
                $conflicts = $partner
                    ? $this->conflictFields($partner, $candidate)
                    : [];

                $status = $partner === null
                    ? 'pending'
                    : (empty($conflicts) ? 'matched' : 'conflict');

                if ($existingCandidate?->status === 'ignored') {
                    $status = 'ignored';
                }

                $partnerTypes = collect($existingCandidate?->partner_types ?? [])
                    ->merge($candidate['partner_types'] ?? [])
                    ->filter(fn ($type) => in_array($type, ['customer', 'supplier'], true))
                    ->unique()
                    ->values()
                    ->all();

                $record = $existingCandidate ?? new PartnerSyncCandidate([
                    'source' => $source,
                    'tax_code' => $taxCode,
                    'first_seen_at' => now(),
                ]);

                $record->fill([
                    'name' => $this->preferIncoming($candidate['name'] ?? null, $record->name),
                    'address' => $this->preferIncoming($candidate['address'] ?? null, $record->address),
                    'email' => $this->preferIncoming($candidate['email'] ?? null, $record->email),
                    'phone' => $this->preferIncoming($candidate['phone'] ?? null, $record->phone),
                    'partner_types' => $partnerTypes,
                    'status' => $status,
                    'matched_partner_id' => $partner?->getKey(),
                    'conflict_fields' => $conflicts ?: null,
                    'source_metadata' => array_merge(
                        $record->source_metadata ?? [],
                        is_array($candidate['source_metadata'] ?? null) ? $candidate['source_metadata'] : []
                    ),
                    'last_seen_at' => now(),
                ]);
                $record->save();

                $summary['total']++;
                $summary[$status]++;
            }
        });

        return $summary;
    }

    private function conflictFields(Partner $partner, array $candidate): array
    {
        $conflicts = [];

        foreach (['name', 'address', 'email', 'phone'] as $field) {
            $incoming = $this->clean($candidate[$field] ?? null);
            if ($incoming === null) {
                continue;
            }

            $local = $this->clean($partner->{$field});
            if ($local === null) {
                $conflicts[$field] = [
                    'state' => 'missing_locally',
                    'local' => null,
                    'incoming' => $incoming,
                ];

                continue;
            }

            if ($this->normalize($local) !== $this->normalize($incoming)) {
                $conflicts[$field] = [
                    'state' => 'different',
                    'local' => $local,
                    'incoming' => $incoming,
                ];
            }
        }

        return $conflicts;
    }

    private function preferIncoming(mixed $incoming, mixed $existing): ?string
    {
        return $this->clean($incoming) ?? $this->clean($existing);
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->value();
    }
}

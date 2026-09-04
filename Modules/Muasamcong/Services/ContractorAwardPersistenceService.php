<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
use RuntimeException;

class ContractorAwardPersistenceService
{
    private const FIELDS = [
        'contractor_name', 'lot_no', 'lot_name', 'medicine_code', 'medicine_name', 'drug_group',
        'active_ingredient', 'concentration', 'route', 'dosage_form', 'unit', 'quantity', 'price_plan',
        'winning_price', 'amount', 'manufacturer', 'country', 'decision_no', 'decision_date', 'published_at',
        'investor_code', 'investor_name', 'contract_no',
    ];

    public function __construct(private readonly ContractorAwardCatalogService $catalog)
    {
    }

    /**
     * Persist the current logical award state into the canonical award warehouse.
     *
     * Imported recovery rows are treated as curated corrections and therefore
     * override non-empty values from automatic/catalog sources. Automatic data
     * still fills any gaps left by the imported row.
     */
    public function persist(ContractorSearch $search, string $notifyNo): array
    {
        $notifyNo = trim($notifyNo);
        $item = $search->items()->where('notify_no', $notifyNo)->first();
        if (! $item) {
            throw new RuntimeException('Mã TBMT không thuộc lịch sử nhà thầu này.');
        }

        $contractorCode = mb_strtolower(trim((string) $search->contractor_code));
        $record = KqlcntRecord::query()
            ->where('contractor_code', $contractorCode)
            ->where('notify_no', $notifyNo)
            ->first();

        $existing = KqlcntAwardItem::query()
            ->where('contractor_code', $contractorCode)
            ->where('notify_no', $notifyNo)
            ->get()
            ->keyBy('identity_key');

        $logical = [];

        foreach ($this->snapshotRows($record) as $row) {
            $this->mergeInto($logical, $this->normalize($row, $search, $record), 'API_SNAPSHOT');
        }

        foreach ($this->catalog->rows($contractorCode, [$notifyNo]) as $row) {
            $normalized = $this->normalize($row, $search, $record);
            $this->mergeInto($logical, $normalized, (string) ($row['source'] ?? 'CATALOG'));
        }

        foreach ($existing as $award) {
            if ($award->source !== 'import') {
                continue;
            }

            $normalized = $this->normalize($award->toArray(), $search, $record);
            $this->mergeInto($logical, $normalized, 'IMPORT', true);
        }

        if ($logical === []) {
            throw new RuntimeException('TBMT chưa có dữ liệu chi tiết để đồng bộ vào cơ sở dữ liệu.');
        }

        $now = now();
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $activeIdentityKeys = [];

        DB::transaction(function () use (
            $logical,
            $existing,
            $search,
            $notifyNo,
            $contractorCode,
            $now,
            &$created,
            &$updated,
            &$unchanged,
            &$activeIdentityKeys
        ): void {
            foreach ($logical as $entry) {
                $row = $entry['row'];
                $identityKey = $this->identityKey($notifyNo, $contractorCode, $row);
                $fingerprint = $this->fingerprint($row);
                $current = $existing->get($identityKey);
                $activeIdentityKeys[] = $identityKey;

                if (! $current) {
                    $created++;
                } elseif (! hash_equals((string) $current->fingerprint, $fingerprint)) {
                    $updated++;
                } else {
                    $unchanged++;
                }

                $source = $current?->source === 'import' ? 'import' : 'catalog';
                $payload = Arr::only($row, self::FIELDS);
                $payload += [
                    'contractor_search_id' => $search->id,
                    'notify_no' => $notifyNo,
                    'contractor_code' => $contractorCode,
                    'identity_key' => $identityKey,
                    'fingerprint' => $fingerprint,
                    'source' => $source,
                    'sync_source' => implode('+', $entry['sources']),
                    'synced_from_catalog_at' => $now,
                    'last_verified_at' => $now,
                    'is_active' => true,
                    'import_batch_id' => $current?->import_batch_id,
                    'raw_payload' => $current?->raw_payload ?: ['canonical' => $row],
                ];

                KqlcntAwardItem::query()->updateOrCreate([
                    'notify_no' => $notifyNo,
                    'contractor_code' => $contractorCode,
                    'identity_key' => $identityKey,
                ], $payload);
            }

            KqlcntAwardItem::query()
                ->where('contractor_code', $contractorCode)
                ->where('notify_no', $notifyNo)
                ->whereNotNull('synced_from_catalog_at')
                ->whereNotIn('identity_key', $activeIdentityKeys)
                ->update(['is_active' => false, 'last_verified_at' => $now]);
        });

        return [
            'count' => count($logical),
            'created' => $created,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'synced_at' => $now,
        ];
    }

    private function mergeInto(array &$logical, array $row, string $source, bool $preferIncoming = false): void
    {
        $key = $this->logicalKey($row);
        if ($key === null) {
            return;
        }

        $tokens = collect(preg_split('/[+|,]/', mb_strtoupper(trim($source))) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        if (! isset($logical[$key])) {
            $logical[$key] = ['row' => $row, 'sources' => $tokens];

            return;
        }

        $base = $logical[$key]['row'];
        foreach (self::FIELDS as $field) {
            $incoming = $row[$field] ?? null;
            $current = $base[$field] ?? null;
            if ($preferIncoming && $this->filled($incoming)) {
                $base[$field] = $incoming;
            } elseif (! $this->filled($current) && $this->filled($incoming)) {
                $base[$field] = $incoming;
            }
        }

        $logical[$key]['row'] = $base;
        $logical[$key]['sources'] = collect($logical[$key]['sources'])
            ->merge($tokens)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(array $row, ContractorSearch $search, ?KqlcntRecord $record): array
    {
        $contractNos = collect((array) $record?->contracts)
            ->map(fn ($contract) => data_get($contract, 'contractNo') ?? data_get($contract, 'contract_no'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();
        $singleContractNo = $contractNos->count() === 1 ? $contractNos->first() : null;

        $normalized = [];
        foreach (self::FIELDS as $field) {
            $normalized[$field] = $row[$field] ?? null;
        }

        $normalized['contractor_name'] = $normalized['contractor_name'] ?: $search->contractor_name;
        $normalized['investor_code'] = $normalized['investor_code'] ?: $record?->investor_code;
        $normalized['investor_name'] = $normalized['investor_name'] ?: $record?->investor_name;
        $normalized['contract_no'] = $normalized['contract_no'] ?: $singleContractNo;

        if (! $this->filled($normalized['amount']) && is_numeric($normalized['quantity']) && is_numeric($normalized['winning_price'])) {
            $normalized['amount'] = (float) $normalized['quantity'] * (float) $normalized['winning_price'];
        }

        return $normalized;
    }

    private function snapshotRows(?KqlcntRecord $record): array
    {
        if (! $record || ! is_array($record->verified_lots)) {
            return [];
        }

        return collect($record->verified_lots)->map(function ($lot): array {
            $lot = is_array($lot) ? $lot : [];
            $raw = is_array($lot['raw_payload'] ?? null) ? $lot['raw_payload'] : [];
            $data = array_replace($raw, $lot);

            return [
                'lot_no' => $data['lot_no'] ?? $data['lotNo'] ?? null,
                'lot_name' => $data['lot_name'] ?? $data['lotName'] ?? null,
                'medicine_code' => $data['medicine_code'] ?? $data['medicineCode'] ?? $data['maThuoc'] ?? null,
                'medicine_name' => $data['medicine_name'] ?? $data['medicineName'] ?? $data['tenThuoc'] ?? null,
                'drug_group' => $data['drug_group'] ?? $data['medicine_group'] ?? $data['nhomThuoc'] ?? null,
                'active_ingredient' => $data['active_ingredient'] ?? $data['tenHoatChat'] ?? null,
                'concentration' => $data['concentration'] ?? $data['nongDo'] ?? null,
                'route' => $data['route'] ?? $data['duongDung'] ?? null,
                'dosage_form' => $data['dosage_form'] ?? $data['dangBaoChe'] ?? null,
                'unit' => $data['unit'] ?? $data['uom'] ?? $data['donViTinh'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'price_plan' => $data['price_plan'] ?? $data['pricePlan'] ?? null,
                'winning_price' => $data['winning_price'] ?? $data['winning_unit_price'] ?? $data['price'] ?? null,
                'amount' => $data['amount'] ?? $data['plan_amount'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? $data['tenCoSoSanXuat'] ?? null,
                'country' => $data['country'] ?? $data['nuocSanXuat'] ?? null,
                'decision_no' => $data['decision_no'] ?? $data['soQuyetDinh'] ?? null,
                'decision_date' => $data['decision_date'] ?? $data['ngayBanHanhQuyetDinh'] ?? null,
                'published_at' => $data['published_at'] ?? $data['ngayDangTaiKqlcnt'] ?? null,
                'investor_code' => $data['investor_code'] ?? null,
                'investor_name' => $data['investor_name'] ?? $data['tenCdtBmt'] ?? null,
                'contract_no' => $data['contract_no'] ?? $data['contractNo'] ?? null,
            ];
        })->all();
    }

    private function logicalKey(array $row): ?string
    {
        foreach (['lot_no', 'medicine_code'] as $field) {
            if ($this->filled($row[$field] ?? null)) {
                return $field.':'.mb_strtoupper(trim((string) $row[$field]));
            }
        }

        $parts = collect(['medicine_name', 'active_ingredient', 'concentration'])
            ->map(fn ($field) => Str::lower(trim((string) ($row[$field] ?? ''))))
            ->filter();

        return $parts->isEmpty() ? null : 'medicine:'.hash('sha256', $parts->implode('|'));
    }

    private function identityKey(string $notifyNo, string $contractorCode, array $row): string
    {
        $identity = $row['lot_no'] ?: ($row['medicine_code'] ?: implode('|', [
            Str::lower((string) $row['medicine_name']),
            Str::lower((string) $row['active_ingredient']),
            Str::lower((string) $row['concentration']),
        ]));

        return hash('sha256', $notifyNo.'|'.$contractorCode.'|'.$identity);
    }

    private function fingerprint(array $row): string
    {
        return hash('sha256', json_encode(Arr::only($row, self::FIELDS), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function filled(mixed $value): bool
    {
        return $value !== null && (! is_string($value) || trim($value) !== '');
    }
}

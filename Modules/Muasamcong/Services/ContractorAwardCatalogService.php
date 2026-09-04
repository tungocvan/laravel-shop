<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Collection;
use Modules\Muasamcong\Models\ContractorManualLot;

class ContractorAwardCatalogService
{
    /**
     * Return one logical award row per saved lot / medicine.
     *
     * Manual HSMT selections and Smart Pricing verification can describe the
     * same awarded line using complementary fields, so they must be merged
     * before counting or exporting.
     */
    public function rows(string $contractorCode, array|Collection $notifyNos): Collection
    {
        $notifyNos = collect($notifyNos)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($notifyNos->isEmpty()) {
            return collect();
        }

        $lots = ContractorManualLot::query()
            ->where('contractor_code', $contractorCode)
            ->whereIn('notify_no', $notifyNos)
            ->whereIn('source', ['manual', 'smart_pricing_verified', 'kqlcnt_verified'])
            ->orderBy('notify_no')
            ->orderBy('id')
            ->get();

        return $lots
            ->groupBy('notify_no')
            ->flatMap(fn (Collection $group) => $this->mergeGroup($group))
            ->values();
    }

    private function mergeGroup(Collection $group): Collection
    {
        $manual = $group->where('source', 'manual')->values();
        $smart = $group->where('source', 'smart_pricing_verified')->values();
        $kqlcnt = $group->where('source', 'kqlcnt_verified')->values();
        $usedSmart = [];
        $rows = collect();

        foreach ($manual as $manualLot) {
            $matchIndex = $this->bestSmartMatch($manualLot, $smart, $usedSmart);
            $smartLot = $matchIndex !== null ? $smart->get($matchIndex) : null;
            if ($matchIndex !== null) {
                $usedSmart[$matchIndex] = true;
            }

            $rows->push($this->mergeLots($manualLot, $smartLot));
        }

        foreach ($smart as $index => $smartLot) {
            if (! isset($usedSmart[$index])) {
                $rows->push($this->mergeLots(null, $smartLot));
            }
        }

        foreach ($kqlcnt as $kqlcntLot) {
            $lotNo = trim((string) $kqlcntLot->lot_no);
            $existingIndex = $lotNo === '' ? null : $rows->search(
                fn (array $row): bool => trim((string) ($row['lot_no'] ?? '')) === $lotNo
            );

            if ($existingIndex !== false && $existingIndex !== null) {
                $rows[$existingIndex] = $this->mergeKqlcnt($rows[$existingIndex], $kqlcntLot);
            } else {
                $rows->push($this->mergeLots($kqlcntLot, null));
            }
        }

        return $rows;
    }

    private function bestSmartMatch(ContractorManualLot $manual, Collection $smart, array $used): ?int
    {
        $bestIndex = null;
        $bestScore = 0;

        foreach ($smart as $index => $candidate) {
            if (isset($used[$index])) {
                continue;
            }

            $score = $this->matchScore($manual, $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestScore >= 4 ? $bestIndex : null;
    }

    private function matchScore(ContractorManualLot $manual, ContractorManualLot $smart): int
    {
        $manualRaw = $this->payload($manual);
        $smartRaw = $this->payload($smart);
        $score = 0;

        $manualQuantity = $this->number($manual->quantity);
        $smartQuantity = $this->number($smart->quantity);
        if ($manualQuantity !== null && $smartQuantity !== null && $this->sameNumber($manualQuantity, $smartQuantity)) {
            $score += 3;
        }

        $manualUnitPrice = $this->number($manualRaw['winning_unit_price'] ?? $manual->price_plan);
        $smartUnitPrice = $this->number($smartRaw['winning_unit_price'] ?? $smart->lot_price);
        if ($manualUnitPrice !== null && $smartUnitPrice !== null && $this->sameNumber($manualUnitPrice, $smartUnitPrice)) {
            $score += 3;
        }

        $manualMedicineCode = $this->text($manualRaw['medicine_code'] ?? $manualRaw['medicineCode'] ?? null);
        $smartMedicineCode = $this->text($smartRaw['medicine_code'] ?? $smartRaw['medicineCode'] ?? null);
        if ($manualMedicineCode !== null && $smartMedicineCode !== null && $manualMedicineCode === $smartMedicineCode) {
            $score += 6;
        }

        $manualName = $this->text($manual->medicine_name ?? $manualRaw['medicine_name'] ?? $manualRaw['tenThuoc'] ?? null);
        $smartName = $this->text($smart->medicine_name ?? $smartRaw['medicine_name'] ?? $smartRaw['tenThuoc'] ?? null);
        if ($manualName !== null && $smartName !== null && mb_strtoupper($manualName) === mb_strtoupper($smartName)) {
            $score += 4;
        }

        $manualIngredient = $this->text($manual->active_ingredient ?? $manualRaw['active_ingredient'] ?? $manualRaw['tenHoatChat'] ?? null);
        $smartIngredient = $this->text($smart->active_ingredient ?? $smartRaw['active_ingredient'] ?? $smartRaw['tenHoatChat'] ?? null);
        if ($manualIngredient !== null && $smartIngredient !== null && mb_strtoupper($manualIngredient) === mb_strtoupper($smartIngredient)) {
            $score += 2;
        }

        return $score;
    }

    private function mergeLots(?ContractorManualLot $primary, ?ContractorManualLot $smart): array
    {
        $base = $primary ?? $smart;
        $primaryRaw = $primary ? $this->payload($primary) : [];
        $smartRaw = $smart ? $this->payload($smart) : [];
        $raw = array_replace($primaryRaw, array_filter($smartRaw, fn ($value) => $value !== null && $value !== ''));

        $quantity = $this->number($primary?->quantity ?? $smart?->quantity);
        $winningPrice = $this->number(
            $smartRaw['winning_unit_price']
                ?? $smart?->lot_price
                ?? $primaryRaw['winning_unit_price']
                ?? null
        );
        $pricePlan = $this->number($primary?->price_plan ?? $raw['price_plan'] ?? null);
        $amount = $quantity !== null && $winningPrice !== null
            ? $quantity * $winningPrice
            : $this->number($smart?->plan_amount ?? $primary?->plan_amount);

        $sources = collect([$primary?->source, $smart?->source])->filter()->unique()->values();

        return [
            'notify_no' => $base?->notify_no,
            'contractor_code' => $base?->contractor_code,
            'contractor_name' => $raw['contractor_name'] ?? $raw['contractorName'] ?? null,
            'lot_no' => $primary?->lot_no ?? $smart?->lot_no,
            'lot_name' => $primary?->lot_name ?? $smart?->lot_name,
            'medicine_code' => $raw['medicine_code'] ?? $raw['medicineCode'] ?? null,
            'medicine_name' => $smart?->medicine_name ?? $primary?->medicine_name ?? $raw['medicine_name'] ?? $raw['tenThuoc'] ?? null,
            'drug_group' => $raw['medicine_group'] ?? $raw['medicineGroup'] ?? $raw['nhomThuoc'] ?? null,
            'active_ingredient' => $smart?->active_ingredient ?? $primary?->active_ingredient ?? $raw['active_ingredient'] ?? $raw['tenHoatChat'] ?? null,
            'concentration' => $raw['concentration'] ?? $raw['nongDo'] ?? null,
            'route' => $raw['route'] ?? $raw['duongDung'] ?? null,
            'dosage_form' => $raw['dosage_form'] ?? $raw['dangBaoChe'] ?? null,
            'unit' => $raw['uom'] ?? $raw['unit'] ?? $raw['donViTinh'] ?? null,
            'quantity' => $quantity,
            'price_plan' => $pricePlan,
            'winning_price' => $winningPrice,
            'amount' => $amount,
            'manufacturer' => $raw['manufacturer'] ?? $raw['tenCoSoSanXuat'] ?? null,
            'country' => $raw['country'] ?? $raw['nuocSanXuat'] ?? null,
            'decision_no' => $raw['decision_no'] ?? $raw['soQuyetDinh'] ?? null,
            'decision_date' => $raw['decision_date'] ?? $raw['ngayBanHanhQuyetDinh'] ?? null,
            'published_at' => $raw['published_at'] ?? $raw['ngayDangTaiKqlcnt'] ?? null,
            'investor_name' => $raw['investor_name'] ?? $raw['tenCdtBmt'] ?? null,
            'contract_no' => $raw['contract_no'] ?? $raw['contractNo'] ?? null,
            'source' => $sources->map(fn (string $source) => match ($source) {
                'smart_pricing_verified' => 'SMART_PRICING',
                'kqlcnt_verified' => 'KQLCNT',
                default => strtoupper($source),
            })->implode('+'),
            'updated_at' => collect([$primary?->confirmed_at, $smart?->confirmed_at])->filter()->max()?->format('Y-m-d H:i:s'),
        ];
    }

    private function mergeKqlcnt(array $row, ContractorManualLot $kqlcnt): array
    {
        $extra = $this->mergeLots($kqlcnt, null);

        foreach ($extra as $key => $value) {
            if (($row[$key] ?? null) === null || ($row[$key] ?? null) === '') {
                $row[$key] = $value;
            }
        }

        $row['source'] = collect(explode('+', (string) ($row['source'] ?? '')))
            ->push('KQLCNT')
            ->filter()
            ->unique()
            ->implode('+');

        return $row;
    }

    private function payload(ContractorManualLot $lot): array
    {
        $raw = is_array($lot->raw_payload) ? $lot->raw_payload : [];
        $nested = is_array($raw['raw_payload'] ?? null) ? $raw['raw_payload'] : [];

        return array_replace($nested, $raw);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function text(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function sameNumber(float $left, float $right): bool
    {
        return abs($left - $right) <= max(0.0001, max(abs($left), abs($right)) * 0.000001);
    }
}

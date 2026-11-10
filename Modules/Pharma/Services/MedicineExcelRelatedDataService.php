<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\Medicine;

/**
 * Read-only related-data preview for the Excel field bank.
 * This does NOT register export columns or modify import/export contracts.
 */
class MedicineExcelRelatedDataService
{
    public const GROUPS = [
        'commercial' => [
            'label' => 'Điều kiện thương mại',
            'fields' => ['cost_price' => 'Giá vốn', 'supplier_name' => 'Tên nhà cung cấp'],
        ],
        'profile' => [
            'label' => 'Hồ sơ sản phẩm',
            'fields' => ['hssp_status' => 'Trạng thái HSSP'],
        ],
        'award' => [
            'label' => 'Thông tin trúng thầu',
            'fields' => [
                'winning_company_name' => 'Công ty trúng thầu',
                'investor_name' => 'Nơi trúng thầu',
                'decision_number' => 'Số quyết định',
                'decision_date' => 'Ngày quyết định',
                'contract_period_text' => 'Thời gian',
                'winning_price' => 'Giá trúng thầu',
                'winning_quantity' => 'Số lượng trúng thầu',
                'allocated_quantity' => 'Số lượng phân bổ',
            ],
        ],
    ];

    public function emptyValues(): array
    {
        $values = [];
        foreach (self::GROUPS as $group) {
            foreach ($group['fields'] as $key => $label) {
                $values[$key] = '';
            }
        }
        return $values;
    }

    public function preview(?Medicine $medicine): array
    {
        $values = $this->emptyValues();
        if (! $medicine) {
            return $values;
        }

        // Use the latest supplier tracking as a deterministic preview, never mix suppliers.
        $supplier = $medicine->supplierTrackings()->orderByDesc('working_date')->orderByDesc('id')->first();
        $values['cost_price'] = $this->display($supplier?->cost_price);
        $values['supplier_name'] = $this->display($supplier?->supplier_name);

        $profile = $medicine->currentProfile()->first();
        $values['hssp_status'] = $profile ? 'Đã có HSSP' : 'Chưa có HSSP';

        $preferences = app(MedicineAwardPreferenceService::class);
        $default = $preferences->defaultFor((int) $medicine->id);
        $rows = $preferences->records((int) $medicine->id);
        $canonical = $medicine->drugBidAwards()->where('is_active', true)
            ->orderByDesc('decision_date')->orderByDesc('id')->get();
        $sources = $medicine->sources()->where('source_system', 'public_tender')
            ->where('source_record_type', 'award_price')->where('is_active', true)
            ->orderByDesc('synced_at')->orderByDesc('id')->get();

        $award = null;
        $source = null;
        if ($default) {
            if ($default['origin'] === 'canonical') {
                $award = $canonical->firstWhere('id', $default['record_id']);
            } else {
                $source = $sources->firstWhere('id', $default['record_id']);
            }
        }
        // Compatibility fallback when no valid default exists: latest visible canonical award.
        // Never silently select the lowest or highest price.
        if (! $award && ! $source) {
            $award = $canonical->first(fn ($row) => ! ($rows['canonical:'.$row->id]->is_hidden ?? false));
        }

        if ($award) {
            foreach (['winning_company_name', 'investor_name', 'decision_number', 'contract_period_text', 'winning_price'] as $key) {
                $values[$key] = $this->display($award->getAttribute($key));
            }
            $values['decision_date'] = $award->decision_date?->format('d/m/Y') ?? '';
            $values['winning_quantity'] = $this->display($award->quantity);
            $allocation = $award->allocations()->where('status', 'active')->sum('allocated_quantity');
            $values['allocated_quantity'] = $allocation > 0 ? $this->display($allocation) : '';
        } elseif ($source) {
            $snapshot = data_get($source->metadata, 'award', []);
            $snapshot = is_array($snapshot) ? $snapshot : [];
            $values['winning_company_name'] = $this->display($snapshot['winningName'] ?? null);
            $values['investor_name'] = $this->display($snapshot['tenCdtBmt'] ?? null);
            $values['decision_number'] = $this->display($snapshot['soQuyetDinh'] ?? null);
            $values['decision_date'] = $this->display($snapshot['ngayBanHanhQuyetDinh'] ?? null);
            $values['winning_price'] = $this->display($snapshot['donGia'] ?? null);
            $values['winning_quantity'] = $this->display($snapshot['soLuong'] ?? null);
            // Public source has no verified allocation or contract period.
        }

        return $values;
    }

    private function display(mixed $value): string
    {
        if (is_array($value)) {
            return implode('; ', array_map('strval', array_filter($value, 'is_scalar')));
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}

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
        $values['hssp_status'] = $profile ? 'Đã có HSSP' : '';

        // One representative award only. Full one-to-many export will be designed separately.
        $award = $medicine->drugBidAwards()->where('is_active', true)
            ->orderByDesc('decision_date')->orderByDesc('id')->first();
        if ($award) {
            foreach (['winning_company_name', 'investor_name', 'decision_number', 'contract_period_text', 'winning_price'] as $key) {
                $values[$key] = $this->display($award->getAttribute($key));
            }
            $values['decision_date'] = $award->decision_date?->format('d/m/Y') ?? '';
            $allocation = $award->allocations()->where('status', 'active')->sum('allocated_quantity');
            $values['allocated_quantity'] = $allocation > 0 ? $this->display($allocation) : '';
        }

        return $values;
    }

    private function display(mixed $value): string
    {
        return $value === null ? '' : trim((string) $value);
    }
}

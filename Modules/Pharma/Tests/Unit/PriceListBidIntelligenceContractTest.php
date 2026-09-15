<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListBidIntelligenceContractTest extends TestCase
{
    #[Test]
    public function price_list_create_uses_batch_bid_intelligence_and_captures_evidence(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));

        $this->assertStringContainsString('BidPriceIntelligenceService', $component);
        $this->assertStringContainsString('PriceBidEvidenceService', $component);
        $this->assertStringContainsString('forItems($items, 20)', $component);
        $this->assertStringContainsString('selectedBidAwardIds', $component);
        $this->assertStringContainsString('showBidHistory(', $component);
        $this->assertStringContainsString('selectBidAward(', $component);
        $this->assertStringContainsString('evidenceService->capture(', $component);
    }

    #[Test]
    public function bid_reference_ui_keeps_bid_price_separate_from_commercial_prices(): void
    {
        $reference = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/partials/bid-reference.blade.php'));
        $history = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/partials/bid-history-modal.blade.php'));

        $this->assertStringContainsString('CÓ KẾT QUẢ TRÚNG THẦU', $reference);
        $this->assertStringContainsString('CHƯA CÓ KẾT QUẢ TRÚNG THẦU', $reference);
        $this->assertStringContainsString('Chỉ dùng làm tham chiếu, không tự thay đổi giá bán.', $reference);
        $this->assertStringContainsString('Xem lịch sử / Chọn kết quả khác', $reference);
        $this->assertStringContainsString('Lịch sử kết quả trúng thầu', $history);
        $this->assertStringContainsString('không thay đổi các mức giá thương mại', $history);
    }

    #[Test]
    public function evidence_schema_survives_award_deletion_and_snapshots_core_fields(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_173000_create_price_list_item_bid_evidence_table.php'));

        $this->assertStringContainsString("constrained('pharma_drug_bid_awards')->nullOnDelete()", $migration);
        foreach (['bid_price', 'quantity', 'decision_number', 'award_date', 'contractor_name', 'source_system', 'captured_at'] as $field) {
            $this->assertStringContainsString("'{$field}'", $migration);
        }
    }
}

<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugBidAwardBatchBContractTest extends TestCase
{
    #[Test]
    public function intelligence_service_uses_canonical_matches_and_latest_decision_date(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/BidPriceIntelligenceService.php'));

        $this->assertStringContainsString("join('pharma_drug_bid_award_matches as bm'", $service);
        $this->assertStringContainsString("orderByDesc('a.decision_date')", $service);
        $this->assertStringContainsString("orderByDesc('a.published_at')", $service);
        $this->assertStringContainsString('forVariant(', $service);
        $this->assertStringContainsString('forPackage(', $service);
        $this->assertStringContainsString('forItems(', $service);
        $this->assertStringContainsString('median:', $service);
    }

    #[Test]
    public function review_workspace_supports_manual_confirmation_rematch_and_ignore(): void
    {
        $workspace = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/ReviewWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/review-workspace.blade.php'));

        $this->assertStringContainsString('confirmSelection(', $workspace);
        $this->assertStringContainsString("'manual_review'", $workspace);
        $this->assertStringContainsString('rematch(', $workspace);
        $this->assertStringContainsString('REVIEW_IGNORED', $workspace);
        $this->assertStringContainsString('Số lượng', $view);
        $this->assertStringContainsString('Đơn giá', $view);
        $this->assertStringContainsString('Quyết định', $view);
        $this->assertStringContainsString('Ngày trúng thầu', $view);
        $this->assertStringContainsString('Nhà thầu', $view);
        $this->assertStringContainsString('Xác nhận liên kết', $view);
    }

    #[Test]
    public function review_route_is_declared_before_numeric_award_routes(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $review = strpos($routes, "'/review'");
        $allocations = strpos($routes, "'/{id}/allocations'");

        $this->assertNotFalse($review);
        $this->assertNotFalse($allocations);
        $this->assertLessThan($allocations, $review);
        $this->assertStringContainsString("name('review')", $routes);
    }
}

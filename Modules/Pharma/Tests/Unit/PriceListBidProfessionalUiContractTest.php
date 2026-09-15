<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListBidProfessionalUiContractTest extends TestCase
{
    #[Test]
    public function price_list_uses_table_first_bid_intelligence_instead_of_expanded_award_cards(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/workspace-bid.blade.php'));
        $this->assertStringContainsString('KQ trúng thầu', $view);
        $this->assertStringContainsString('Đã có', $view);
        $this->assertStringContainsString('Bổ sung', $view);
        $this->assertStringContainsString('wire:click="showBidHistory', $view);
        $this->assertStringContainsString('wire:click="openManualBid', $view);
        $this->assertStringNotContainsString('Kết quả trúng thầu của sản phẩm đã chọn', $view);
    }

    #[Test]
    public function responsive_workspace_keeps_bid_evidence_separate_from_commercial_prices(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/workspace-bid.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $this->assertStringContainsString('md:hidden', $view);
        $this->assertStringContainsString('lg:hidden', $view);
        $this->assertStringContainsString('selectedBidAwardIds', $component);
        $this->assertStringNotContainsString("prices[$key]['company'] = $award", $component);
        $this->assertStringNotContainsString("prices[$key]['receivable'] = $award", $component);
        $this->assertStringNotContainsString("prices[$key]['invoice'] = $award", $component);
    }

    #[Test]
    public function manual_bid_is_audited_and_linked_to_canonical_master(): void
    {
        $workspace = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Workspace.php'));
        $this->assertStringContainsString('saveManualBid()', $workspace);
        $this->assertStringContainsString("'source_type' => DrugBidAward::SOURCE_MANUAL", $workspace);
        $this->assertStringContainsString("'created_by' => auth('admin')->id()", $workspace);
        $this->assertStringContainsString('matchManager->confirm(', $workspace);
    }

    #[Test]
    public function create_and_edit_pages_are_full_width_workspaces(): void
    {
        $create = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/create.blade.php'));
        $edit = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/edit.blade.php'));
        $this->assertStringContainsString('w-full', $create);
        $this->assertStringContainsString('w-full', $edit);
        $this->assertStringNotContainsString('max-w-[1600px]', $edit);
    }
}

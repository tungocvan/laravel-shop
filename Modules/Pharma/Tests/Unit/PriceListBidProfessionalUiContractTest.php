<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListBidProfessionalUiContractTest extends TestCase
{
    #[Test]
    public function price_list_uses_table_first_bid_intelligence_instead_of_expanded_award_cards(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));

        $this->assertStringContainsString('KQ trúng thầu', $view);
        $this->assertStringContainsString('Đã có', $view);
        $this->assertStringContainsString('Bổ sung', $view);
        $this->assertStringContainsString("wire:click=\"showBidHistory", $view);
        $this->assertStringNotContainsString('Kết quả trúng thầu của sản phẩm đã chọn', $view);
    }

    #[Test]
    public function bid_history_is_shown_in_a_modal_and_never_writes_commercial_price_inputs(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));

        $this->assertStringContainsString('Lịch sử kết quả trúng thầu', $view);
        $this->assertStringContainsString('selectBidAward(', $component);
        $this->assertStringContainsString('selectedBidAwardIds', $component);
        $this->assertStringNotContainsString("prices[$key]['company'] = $award", $component);
        $this->assertStringNotContainsString("prices[$key]['receivable'] = $award", $component);
        $this->assertStringNotContainsString("prices[$key]['invoice'] = $award", $component);
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

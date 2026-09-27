<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugBidAwardLegalInfoSaveContractTest extends TestCase
{
    public function test_edit_save_validates_only_tbmt_legal_fields(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/Form.php');

        $this->assertStringContainsString("\$data = \$this->isEditMode", $component);
        $this->assertStringContainsString("'decision_document_url' => 'nullable|url|max:255'", $component);

        $editValidation = strstr($component, '$data = $this->isEditMode');
        $editValidation = substr($editValidation, 0, strpos($editValidation, ': $this->validate();') + strlen(': $this->validate();'));

        $this->assertStringNotContainsString("'medicine_name' =>", $editValidation);
        $this->assertStringNotContainsString("'packaging_specification' =>", $editValidation);
        $this->assertStringNotContainsString("'quantity' =>", $editValidation);
        $this->assertStringNotContainsString("'unit_price' =>", $editValidation);
        $this->assertStringNotContainsString("'winning_company_name' =>", $editValidation);
    }

    public function test_edit_hydrates_contract_duration_from_result_group_and_index_has_no_hssp_filter(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/Form.php');
        $service = file_get_contents($root.'/Services/DrugBidAwardService.php');
        $index = file_get_contents($root.'/Livewire/DrugBidAward/Index.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/index.blade.php');

        $this->assertStringContainsString('legalInfoForResultGroup', $service);
        $this->assertStringContainsString("\$legalInfo = \$service->legalInfoForResultGroup(\$id)", $component);
        $this->assertStringContainsString('contractDurationMonthsForResultGroup', $service);
        $this->assertStringContainsString("\$this->contract_duration_months = \$service->contractDurationMonthsForResultGroup(\$id)", $component);
        $this->assertStringNotContainsString('filterMatchStatus', $index);
        $this->assertStringNotContainsString('Đối soát HSSP<select', $view);
    }


    public function test_index_import_export_no_longer_references_removed_match_filter(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/index.blade.php');

        $this->assertStringNotContainsString('$filterMatchStatus', $view);
        $this->assertStringContainsString('xl:grid-cols-[minmax(145px,0.7fr)_minmax(250px,1.45fr)_minmax(250px,1.45fr)', $view);
        $this->assertStringContainsString('items-end gap-3', $view);
        $this->assertStringContainsString('{{ $option }} / trang', $view);
        $this->assertStringContainsString('h-11 whitespace-nowrap', $view);
    }


    public function test_index_uses_compact_business_setup_filter_instead_of_source_filter(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/index.blade.php');
        $component = file_get_contents($root.'/Livewire/DrugBidAward/Index.php');
        $service = file_get_contents($root.'/Services/DrugBidAwardService.php');

        $this->assertStringContainsString('wire:model.live="filterBusinessSetup"', $view);
        $this->assertStringContainsString('Chưa có CS kinh doanh', $view);
        $this->assertStringContainsString('Chưa phân bổ SL', $view);
        $this->assertStringNotContainsString('wire:model.live="filterSource"', $view);
        $this->assertStringContainsString('minmax(145px,0.7fr)', $view);
        $this->assertStringContainsString('minmax(105px,0.5fr)', $view);
        $this->assertStringContainsString('public string $filterBusinessSetup', $component);
        $this->assertStringContainsString("businessSetup === 'commercial_missing'", $service);
        $this->assertStringContainsString("businessSetup === 'allocation_ready'", $service);
    }


    public function test_result_group_action_menu_is_teleported_outside_table_scroll_container(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/drug-bid-award/index.blade.php');

        $this->assertStringContainsString('overflow-x-auto overflow-y-visible', $view);
        $this->assertStringContainsString('x-teleport="body"', $view);
        $this->assertStringContainsString('getBoundingClientRect()', $view);
        $this->assertStringContainsString('position: fixed; top:', $view);
        $this->assertStringContainsString('z-[100]', $view);
        $this->assertStringContainsString('x-on:click.outside="open = false"', $view);
        $this->assertStringNotContainsString('<details class="relative z-40">', $view);
    }

}

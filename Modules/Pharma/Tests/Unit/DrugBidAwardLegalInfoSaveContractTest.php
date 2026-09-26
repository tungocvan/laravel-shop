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
        $this->assertStringContainsString("\$this->contract_duration_months = \$legalInfo->contract_duration_months", $component);
        $this->assertStringNotContainsString('filterMatchStatus', $index);
        $this->assertStringNotContainsString('Đối soát HSSP<select', $view);
    }

}

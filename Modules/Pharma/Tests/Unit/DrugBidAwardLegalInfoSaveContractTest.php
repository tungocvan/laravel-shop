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
}

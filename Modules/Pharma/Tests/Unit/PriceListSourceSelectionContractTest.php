<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListSourceSelectionContractTest extends TestCase
{
    #[Test]
    public function customer_draft_keeps_source_for_traceability_without_reinitializing_saved_skus(): void
    {
        $workspace = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Workspace.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceList.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_170000_add_source_price_list_to_price_lists.php'));
        $create = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/create.blade.php'));
        $edit = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/edit.blade.php'));

        $this->assertStringContainsString('source_price_list_id', $migration);
        $this->assertStringContainsString('source_price_list_id', $model);
        $this->assertStringContainsString('sourcePriceList', $model);
        $this->assertStringContainsString("@livewire('pharma.price-list.workspace')", $create);
        $this->assertStringContainsString("@livewire('pharma.price-list.workspace', ['priceListId' => \$priceList->id])", $edit);

        $this->assertStringContainsString('$this->sourceGlobalPriceListId = $this->persistedSourceGlobalPriceListId', $workspace);
        $this->assertStringContainsString('if ($this->priceListId)', $workspace);
        $this->assertStringContainsString("count(\$this->includedRows).' SKU đã lưu'", $workspace);
        $this->assertStringContainsString('parent::loadFromGlobalPriceList()', $workspace);
        $this->assertStringContainsString("'source_price_list_id' => \$sourceId", $workspace);
    }
}

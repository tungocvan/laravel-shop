<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierTrackingPurchasePriceFilterContractTest extends TestCase
{
    #[Test]
    public function supplier_tracking_supports_distinct_purchase_price_states(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));

        $this->assertStringContainsString("public string \$purchasePrice = ''", $component);
        $this->assertStringContainsString("'purchase_price' => \$this->purchasePrice", $component);
        $this->assertStringContainsString("'purchasePrice' => ['except' => '']", $component);
        $this->assertStringContainsString("'with' => \$query->whereNotNull('import_price')->where('import_price', '>', 0)", $service);
        $this->assertStringContainsString("'missing' => \$query->whereNull('import_price')", $service);
        $this->assertStringContainsString("'zero' => \$query->whereNotNull('import_price')->where('import_price', 0)", $service);
        $this->assertStringContainsString('Giá mua', $view);
        $this->assertStringContainsString('Có giá (&gt; 0)', $view);
        $this->assertStringContainsString('Chưa có giá', $view);
        $this->assertStringContainsString('Giá 0 đồng', $view);
        $this->assertStringContainsString("wire:model.live=\"purchasePrice\"", $view);
    }
}

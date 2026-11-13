<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaGlobalPriceListDirectCreateContractTest extends TestCase
{
    #[Test]
    public function global_creation_uses_medicine_master_and_preserves_customer_workflow(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/ApproverGlobalPriceListWorkflow.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/global-price-list-create.blade.php'));

        $this->assertStringContainsString("request->query('type') === 'global'", $controller);
        $this->assertStringContainsString('MedicineVariant::query()', $controller);
        $this->assertStringContainsString("where('status', 'active')", $controller);
        $this->assertStringContainsString("view('ClientPortal::applications.pharma.global-price-list-create'", $controller);
        $this->assertStringContainsString("view('ClientPortal::applications.pharma.price-list-create'", $controller);
        $this->assertStringContainsString("client.pharma.price-lists.approve", $controller);
        $this->assertStringContainsString("if ($sourceId > 0 && ! $source)", $workflow);
        $this->assertStringContainsString('MedicineVariant::query()', $workflow);
        $this->assertStringContainsString('DB::transaction(', $workflow);
        $this->assertStringContainsString("client.pharma.price-lists.global.store", $view);
        $this->assertStringContainsString('name="selected[', $view);
        $this->assertStringContainsString('name="global_user_ids[]"', $view);
        $this->assertStringContainsString('name="apply_all_users"', $view);
        $this->assertStringContainsString('name="q"', $view);
        $this->assertStringContainsString('data-global-price', $view);
    }
}

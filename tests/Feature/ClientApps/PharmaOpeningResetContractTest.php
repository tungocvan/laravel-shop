<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOpeningResetContractTest extends TestCase
{
    public function test_reset_is_guarded_by_permission_confirmation_and_backup(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaInventoryReportsController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-opening.blade.php'));
        $this->assertStringContainsString("->name('inventory.opening.reset')", $routes);
        $this->assertStringContainsString('public function resetOpening(', $controller);
        $this->assertStringContainsString('$this->authorizeInventory($request, $registry, true);', $controller);
        $this->assertStringContainsString("'confirmation' => 'required|string|in:XOA TON DAU KY'", $controller);
        $this->assertStringContainsString("'backup_confirmed' => 'required|accepted'", $controller);
        $this->assertStringContainsString("route('client.pharma.inventory.opening.reset')", $view);
        $this->assertStringContainsString('data-pwa-file-handoff', $view);
    }

    public function test_reset_service_rejects_documents_movements_and_inconsistent_ledger(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/OpeningInventoryResetService.php'));
        $this->assertStringContainsString('InventoryReceipt::query()->exists()', $service);
        $this->assertStringContainsString('InventoryIssue::query()->exists()', $service);
        $this->assertStringContainsString("where('type', '!=', 'opening')->exists()", $service);
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("InventoryTransaction::query()->where('type', 'opening')->delete()", $service);
        $this->assertStringContainsString('InventoryBalance::query()->delete()', $service);
        $this->assertStringNotContainsString('truncate(', $service);
    }
}

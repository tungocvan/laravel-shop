<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaInventoryOpeningWorkspaceContractTest extends TestCase
{
    public function test_opening_workspace_uses_shared_search_components_and_shows_lot_table(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-opening.blade.php'));
        $this->assertStringContainsString('<x-pwa-select-search', $view);
        $this->assertStringContainsString('data-pwa-select-search-option', $view);
        $this->assertStringContainsString('id="opening-medicine"', $view);
        $this->assertStringContainsString('<x-pwa-search', $view);
        $this->assertStringContainsString('$openingLots as $lot', $view);
        $this->assertStringContainsString("route('client.pharma.inventory.opening.export')", $view);
        $this->assertStringContainsString('data-pwa-file-handoff', $view);
    }

    public function test_opening_lot_correction_requires_authorization_and_pristine_ledger(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaInventoryReportsController.php'));
        $editor = file_get_contents(base_path('Modules/Pharma/Services/OpeningLotEditor.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-opening.blade.php'));

        $this->assertStringContainsString("->name('inventory.opening.update')", $routes);
        $this->assertStringContainsString('public function updateOpening(', $controller);
        $this->assertStringContainsString('$this->authorizeInventory($request, $registry, true);', $controller);
        $this->assertStringContainsString('DB::transaction(', $editor);
        $this->assertStringContainsString('lockForUpdate()', $editor);
        $this->assertStringContainsString("\$transactions->count() !== 1", $editor);
        $this->assertStringContainsString("->first()->type !== 'opening'", $editor);
        $this->assertStringContainsString("'quantity_on_hand' => \$quantity", $editor);
        $this->assertStringContainsString("'balance_after' => \$quantity", $editor);
        $this->assertStringContainsString('data-opening-edit', $view);
        $this->assertStringContainsString('setTimeout(() =>', $view);
        $this->assertStringContainsString('}, 350)', $view);
        $this->assertStringNotContainsString('>Tìm kiếm</button>', $view);
        $this->assertStringContainsString('<x-pwa-date name="expiry_date" label="Hạn dùng" required />', $view);
    }

    public function test_export_preserves_import_columns_and_write_permission(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaInventoryReportsController.php'));
        $this->assertStringContainsString("->name('inventory.opening.export')", $routes);
        $this->assertStringContainsString('public function exportOpening(', $controller);
        $this->assertStringContainsString('$this->authorizeInventory($request, $registry, true);', $controller);
        $this->assertStringContainsString("->where('opening_quantity', '>', 0)", $controller);
        $this->assertStringContainsString("'Ma thuoc' =>", $controller);
        $this->assertStringContainsString("'So lo' =>", $controller);
        $this->assertStringContainsString("'Han dung' =>", $controller);
        $this->assertStringContainsString("'Ton dau ky' =>", $controller);
        $this->assertStringContainsString('đã phát sinh tồn, không thể nhập lại', $controller);
    }
}

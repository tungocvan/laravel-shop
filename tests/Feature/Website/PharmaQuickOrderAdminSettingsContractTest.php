<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaQuickOrderSettingsService;
use Tests\TestCase;

class PharmaQuickOrderAdminSettingsContractTest extends TestCase
{
    public function test_defaults_are_disabled_and_fail_closed(): void
    {
        $resolved = app(PharmaQuickOrderSettingsService::class)->normalize([]);

        $this->assertSame(['enabled' => false, 'price_list_ids' => []], $resolved);
    }

    public function test_settings_normalize_duplicate_and_invalid_identifiers(): void
    {
        $resolved = app(PharmaQuickOrderSettingsService::class)->normalize([
            'enabled' => true,
            'price_list_ids' => [3, '3', 0, -2, 5],
        ]);

        $this->assertSame(['enabled' => true, 'price_list_ids' => [3, 5]], $resolved);
    }

    public function test_admin_routes_enforce_admin_guard_and_settings_permission(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString("auth:admin", $routes);
        $this->assertStringContainsString("permission:website.settings.manage,admin", $routes);
        $this->assertStringContainsString("PharmaQuickOrderSettingsController", $routes);
        $this->assertStringContainsString("pharma-quick-order", $routes);
    }

    public function test_admin_save_validates_active_sources_and_has_no_public_order_endpoint(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderSettingsController.php'));
        $this->assertStringContainsString("PriceList::STATUS_ACTIVE", $controller);
        $this->assertStringContainsString("'distinct'", $controller);
        $this->assertStringContainsString("->withErrors(", $controller);
        $this->assertStringNotContainsString('createDraft(', $controller);
    }
}

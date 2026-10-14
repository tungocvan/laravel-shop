<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaQuickOrderSettingsService;
use Tests\TestCase;

class PharmaQuickOrderAdminSettingsContractTest extends TestCase
{
    public function test_defaults_are_disabled_and_fail_closed(): void
    {
        $resolved = app(PharmaQuickOrderSettingsService::class)->normalize([]);

        $this->assertSame(['enabled' => false, 'notification_email' => '', 'price_list_ids' => []], $resolved);
    }

    public function test_settings_normalize_duplicate_and_invalid_identifiers(): void
    {
        $resolved = app(PharmaQuickOrderSettingsService::class)->normalize([
            'enabled' => true,
            'price_list_ids' => [3, '3', 0, -2, 5],
        ]);

        $this->assertSame(['enabled' => true, 'notification_email' => '', 'price_list_ids' => [3, 5]], $resolved);
    }

    public function test_admin_can_configure_notification_email(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/index.blade.php'));
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderSettingsController.php'));
        $this->assertStringContainsString('name="notification_emails[]"', $view);
        $this->assertStringContainsString('quick-order-add-email', $view);
        $this->assertStringContainsString('data-remove-email', $view);
        $this->assertStringContainsString('parseNotificationEmails(', $view);
        $this->assertStringContainsString("'notification_email' => ['nullable', 'string', 'max:2550']", $controller);
        $this->assertStringContainsString("'notification_emails' => ['sometimes', 'array', 'max:10']", $controller);
        $this->assertStringContainsString("'notification_emails.*' => ['nullable', 'email:rfc', 'max:254']", $controller);
        $this->assertStringContainsString('parseNotificationEmails(', $controller);
    }

    public function test_notification_emails_accept_both_separators_and_deduplicate(): void
    {
        $settings = app(PharmaQuickOrderSettingsService::class);
        $this->assertSame(
            ['first@example.com', 'second@example.com'],
            $settings->parseNotificationEmails(' first@example.com ; second@example.com,first@example.com ')
        );
        $this->assertSame(
            'first@example.com; second@example.com',
            $settings->normalize(['notification_email' => 'first@example.com, second@example.com'])['notification_email']
        );
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
    public function test_pharma_homepage_workspace_links_to_admin_settings_only_for_authorized_admin(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));
        $this->assertStringContainsString("hasPermissionTo('website.settings.manage', 'admin')", $view);
        $this->assertStringContainsString("route('admin.website.pharma-quick-order.index')", $view);
        $this->assertStringContainsString('Đặt hàng nhanh Pharma — nguồn bảng giá', $view);
    }

}

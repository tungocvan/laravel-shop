<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Tests\TestCase;

class ClientPortalPharmaHubOverviewContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharma_overview_has_existing_defaults_and_persists_admin_overrides(): void
    {
        $manifest = app(ApplicationRegistry::class)->find('pharma');
        $this->assertNotNull($manifest);

        $settings = app(ClientPortalSettingsService::class);
        $defaults = $settings->applicationHubPresentation($manifest);

        $this->assertTrue($defaults['overview_visible']);
        $this->assertSame('Tổng quan', $defaults['overview_eyebrow']);
        $this->assertSame('Workspace được cấp quyền', $defaults['overview_title']);
        $this->assertSame('Chức năng được cấp quyền', $defaults['capabilities_eyebrow']);
        $this->assertSame('Chọn chức năng', $defaults['capabilities_title']);

        $settings->updateApplicationHubPresentation('pharma', [
            'overview_visible' => false,
            'overview_eyebrow' => 'GIỚI THIỆU',
            'overview_title' => 'Các ứng dụng của bạn',
            'overview_description' => 'Chọn chức năng phù hợp.',
            'capabilities_eyebrow' => 'Công cụ của bạn',
            'capabilities_title' => 'Bắt đầu công việc',
        ]);

        $updated = $settings->applicationHubPresentation($manifest);
        $this->assertFalse($updated['overview_visible']);
        $this->assertSame('GIỚI THIỆU', $updated['overview_eyebrow']);
        $this->assertSame('Các ứng dụng của bạn', $updated['overview_title']);
        $this->assertSame('Chọn chức năng phù hợp.', $updated['overview_description']);
        $this->assertSame('Công cụ của bạn', $updated['capabilities_eyebrow']);
        $this->assertSame('Bắt đầu công việc', $updated['capabilities_title']);
        $this->assertDatabaseHas('client_portal_settings', [
            'group_name' => 'application.pharma.hub',
            'key' => 'overview_visible',
            'type' => 'boolean',
        ]);
    }

    public function test_admin_form_and_pharma_hub_share_managed_overview_fields(): void
    {
        $admin = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/application-presentation.blade.php'));
        $hub = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/dashboard.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/Admin/PwaSettingsController.php'));

        foreach (['overview_visible', 'overview_eyebrow', 'overview_title', 'overview_description', 'capabilities_eyebrow', 'capabilities_title'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $admin);
            $this->assertStringContainsString("'".$field."'", $controller);
            $this->assertStringContainsString("'".$field."'", $hub);
        }

        $this->assertStringContainsString("@if(\$hubPresentation['overview_visible'] ?? true)", $hub);
        $this->assertStringContainsString("{{ \$features->count() }}", $hub);
        $this->assertStringContainsString('@forelse($features as $feature)', $hub);
    }
}

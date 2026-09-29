<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Models\ClientPortalSetting;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Tests\TestCase;

class ClientPortalPwaSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pwa_settings_use_module_defaults_before_admin_overrides(): void
    {
        $settings = app(ClientPortalSettingsService::class);

        $this->assertSame(config('clientportal.pwa.general.application_name'), $settings->pwaGeneral()['application_name']);
        $this->assertSame(config('clientportal.pwa.login.heading'), $settings->pwaLogin()['heading']);
        $this->assertSame(config('clientportal.pwa.login.feature_cards'), $settings->pwaLogin()['feature_cards']);
        $this->assertSame(config('clientportal.pwa.launcher.heading'), $settings->pwaLauncher()['heading']);
    }

    public function test_admin_overrides_are_persisted_and_merged_with_defaults(): void
    {
        $settings = app(ClientPortalSettingsService::class);

        $settings->updatePwaGeneral([
            'application_name' => 'Client Workspace',
            'theme_color' => '#123456',
        ], 99);

        $general = $settings->pwaGeneral();

        $this->assertSame('Client Workspace', $general['application_name']);
        $this->assertSame('#123456', $general['theme_color']);
        $this->assertSame(config('clientportal.pwa.general.short_name'), $general['short_name']);
        $this->assertDatabaseHas('client_portal_settings', [
            'group_name' => 'pwa.general',
            'key' => 'application_name',
            'updated_by' => 99,
        ]);
    }

    public function test_setting_keys_are_isolated_by_group(): void
    {
        $settings = app(ClientPortalSettingsService::class);

        $settings->updatePwaGeneral(['application_name' => 'General Name']);
        $settings->updatePwaLogin(['application_name' => 'Login Name']);

        $this->assertDatabaseHas('client_portal_settings', ['group_name' => 'pwa.general', 'key' => 'application_name']);
        $this->assertDatabaseHas('client_portal_settings', ['group_name' => 'pwa.login', 'key' => 'application_name']);
    }

    public function test_login_feature_cards_are_data_driven(): void
    {
        $settings = app(ClientPortalSettingsService::class);
        $cards = [
            ['enabled' => true, 'title' => 'Tra cứu nhanh', 'description' => 'Nội dung do Admin cấu hình.'],
            ['enabled' => false, 'title' => 'Ẩn tạm thời', 'description' => 'Không hiển thị ở login.'],
        ];

        $settings->updatePwaLogin(['feature_cards' => $cards]);

        $this->assertSame($cards, $settings->pwaLogin()['feature_cards']);
        $this->assertSame('json', ClientPortalSetting::query()->where('group_name', 'pwa.login')->where('key', 'feature_cards')->value('type'));
    }

    public function test_launcher_content_is_data_driven(): void
    {
        $settings = app(ClientPortalSettingsService::class);

        $settings->updatePwaLauncher([
            'heading' => 'Kho ứng dụng nội bộ',
            'show_source_module' => false,
        ], 88);

        $launcher = $settings->pwaLauncher();

        $this->assertSame('Kho ứng dụng nội bộ', $launcher['heading']);
        $this->assertFalse($launcher['show_source_module']);
        $this->assertDatabaseHas('client_portal_settings', [
            'group_name' => 'pwa.launcher',
            'key' => 'heading',
            'updated_by' => 88,
        ]);
    }

    public function test_registry_preserves_feature_page_presentation_defaults(): void
    {
        $application = app(ApplicationRegistry::class)->find('pharma');
        $feature = collect($application['features'])->firstWhere('key', 'commercial');

        $this->assertNotNull($feature);
        $this->assertSame('Commercial Workspace', $feature['eyebrow']);
        $this->assertSame('Công việc bệnh viện của tôi', $feature['page_title']);
        $this->assertSame('Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.', $feature['page_description']);
        $this->assertSame('client.pharma.commercial', $feature['route']);
        $this->assertSame('client.pharma.commercial.view', $feature['permission']);
    }

    public function test_feature_page_content_uses_manifest_defaults_and_admin_overrides(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('pharma');
        $feature = collect($application['features'])->firstWhere('key', 'commercial');

        $this->assertNotNull($feature);
        $defaults = $settings->featurePresentation('pharma', $feature);
        $this->assertSame('Commercial Workspace', $defaults['eyebrow']);
        $this->assertSame('Công việc bệnh viện của tôi', $defaults['page_title']);
        $this->assertSame('Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.', $defaults['page_description']);

        $settings->updateFeaturePresentation('pharma', 'commercial', [
            'eyebrow' => 'Không gian thương mại',
            'page_title' => 'Bệnh viện phụ trách',
            'page_description' => 'Nội dung do Admin quản lý.',
        ], 77);

        $presentation = $settings->featurePresentation('pharma', $feature);
        $this->assertSame('Không gian thương mại', $presentation['eyebrow']);
        $this->assertSame('Bệnh viện phụ trách', $presentation['page_title']);
        $this->assertSame('Nội dung do Admin quản lý.', $presentation['page_description']);
        $this->assertDatabaseHas('client_portal_settings', [
            'group_name' => 'application.pharma.feature.commercial.presentation',
            'key' => 'page_title',
            'updated_by' => 77,
        ]);
    }

    public function test_application_presentation_override_preserves_manifest_contract(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('muasamcong');

        $this->assertNotNull($application);
        $originalRoute = $application['route'];
        $originalPermission = $application['permission'];

        $settings->updateApplicationPresentation('muasamcong', [
            'enabled' => true,
            'name' => 'Tra cứu mua sắm công',
            'description' => 'Tên và mô tả do Admin cấu hình.',
            'sort_order' => 1,
        ]);

        $presented = $settings->presentApplications(collect([$application]))->first();

        $this->assertSame('Tra cứu mua sắm công', $presented['name']);
        $this->assertSame('Tên và mô tả do Admin cấu hình.', $presented['description']);
        $this->assertSame(1, $presented['sort_order']);
        $this->assertSame($originalRoute, $presented['route']);
        $this->assertSame($originalPermission, $presented['permission']);
    }

    public function test_application_can_be_hidden_from_launcher_without_changing_manifest(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('muasamcong');

        $this->assertNotNull($application);
        $settings->updateApplicationPresentation('muasamcong', ['enabled' => false]);

        $this->assertTrue($settings->presentApplications(collect([$application]))->isEmpty());
        $this->assertNotNull($registry->find('muasamcong'));
    }

    public function test_login_blade_reads_pwa_settings_instead_of_hard_coded_content(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/login.blade.php'));

        $this->assertStringContainsString("\$pwaLogin['heading']", $blade);
        $this->assertStringContainsString("\$pwaLogin['feature_cards']", $blade);
        $this->assertStringNotContainsString('Cài như ứng dụng', $blade);
        $this->assertStringNotContainsString('Một nơi để mở tất cả ứng dụng công việc của bạn.', $blade);
    }

    public function test_launcher_blade_reads_settings_instead_of_hard_coded_copy(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/apps.blade.php'));

        $this->assertStringContainsString("\$launcher['heading']", $blade);
        $this->assertStringContainsString("\$launcher['open_application_text']", $blade);
        $this->assertStringNotContainsString('Chọn ứng dụng được quản trị viên cấp quyền.', $blade);
        $this->assertStringNotContainsString('Chưa có ứng dụng được cấp</h2>', $blade);
    }

    public function test_feature_admin_form_exposes_safe_page_copy_fields(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/Admin/PwaSettingsController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/application-presentation.blade.php'));

        $this->assertStringContainsString("'eyebrow' => ['required', 'string', 'max:80']", $controller);
        $this->assertStringContainsString("'page_title' => ['required', 'string', 'max:160']", $controller);
        $this->assertStringContainsString("'page_description' => ['nullable', 'string', 'max:500']", $controller);
        $this->assertStringContainsString('name="eyebrow"', $view);
        $this->assertStringContainsString('name="page_title"', $view);
        $this->assertStringContainsString('name="page_description"', $view);
        $this->assertStringContainsString('Route, permission và nghiệp vụ vẫn do source code kiểm soát.', $view);
        $this->assertStringNotContainsString('name="route"', $view);
        $this->assertStringNotContainsString('name="permission"', $view);
    }

    public function test_pwa_admin_routes_are_protected_by_admin_guard_and_edit_permission(): void
    {
        foreach ([
            'admin.client-apps.pwa.edit',
            'admin.client-apps.pwa.general.update',
            'admin.client-apps.pwa.login.update',
            'admin.client-apps.pwa.launcher.edit',
            'admin.client-apps.pwa.launcher.update',
            'admin.client-apps.pwa.applications.update',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name);
            $this->assertContains('auth:admin', $route->gatherMiddleware(), $name);
            $this->assertContains('permission:edit_role,admin', $route->gatherMiddleware(), $name);
        }
    }
}

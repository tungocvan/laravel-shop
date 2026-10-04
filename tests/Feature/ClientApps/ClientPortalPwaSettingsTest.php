<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Models\ClientPortalSetting;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\System\Models\Setting;
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

    public function test_all_routable_features_define_managed_page_content_defaults(): void
    {
        $applications = app(ApplicationRegistry::class)->all();

        foreach ($applications as $application) {
            foreach ($application['features'] as $feature) {
                if ($feature['route'] === null) {
                    continue;
                }

                $this->assertNotSame('', trim((string) $feature['eyebrow']), $application['key'].'.'.$feature['key'].' eyebrow');
                $this->assertNotSame('', trim((string) $feature['page_title']), $application['key'].'.'.$feature['key'].' page_title');
                $this->assertArrayHasKey('page_description', $feature, $application['key'].'.'.$feature['key'].' page_description');
            }
        }
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

    public function test_application_hub_uses_manifest_defaults_and_admin_overrides(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('pharma');

        $this->assertNotNull($application);
        $defaults = $settings->applicationHubPresentation($application);
        $this->assertSame('Pharma PWA', $defaults['eyebrow']);
        $this->assertSame('Không gian làm việc Pharma', $defaults['title']);
        $this->assertSame('Ranh giới Foundation', $defaults['supporting_title']);
        $this->assertTrue($defaults['supporting_visible']);

        $settings->updateApplicationHubPresentation('pharma', [
            'eyebrow' => 'Pharma Workspace',
            'title' => 'Không gian Pharma',
            'description' => 'Nội dung Hub do Admin quản lý.',
            'supporting_visible' => false,
            'supporting_title' => 'Ghi chú',
            'supporting_body' => 'Nội dung hỗ trợ.',
        ], 76);

        $presentation = $settings->applicationHubPresentation($application);
        $this->assertSame('Pharma Workspace', $presentation['eyebrow']);
        $this->assertSame('Không gian Pharma', $presentation['title']);
        $this->assertFalse($presentation['supporting_visible']);
        $this->assertDatabaseHas('client_portal_settings', [
            'group_name' => 'application.pharma.hub',
            'key' => 'title',
            'updated_by' => 76,
        ]);
    }

    public function test_application_hub_keeps_legacy_overview_copy_as_compatibility_fallback(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('pharma');

        $settings->updateFeaturePresentation('pharma', 'overview', [
            'eyebrow' => 'Legacy eyebrow',
            'page_title' => 'Legacy title',
            'page_description' => 'Legacy description',
        ]);

        $presentation = $settings->applicationHubPresentation($application);
        $this->assertSame('Legacy eyebrow', $presentation['eyebrow']);
        $this->assertSame('Legacy title', $presentation['title']);
        $this->assertSame('Legacy description', $presentation['description']);
        $this->assertSame('Ranh giới Foundation', $presentation['supporting_title']);
    }

    public function test_unrelated_legacy_overview_setting_does_not_override_hub_copy(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('pharma');

        $settings->updateFeaturePresentation('pharma', 'overview', [
            'maintenance' => true,
            'maintenance_message' => 'Legacy maintenance only',
        ]);

        $presentation = $settings->applicationHubPresentation($application);
        $this->assertSame('Pharma PWA', $presentation['eyebrow']);
        $this->assertSame('Không gian làm việc Pharma', $presentation['title']);
        $this->assertSame($application['hub']['description'], $presentation['description']);
    }

    public function test_global_bottom_navigation_appearance_defaults_match_existing_mobile_shell(): void
    {
        $settings = app(ClientPortalSettingsService::class);
        $bottom = $settings->pwaBottomNavigation();

        $this->assertSame('default', $bottom['presentation_style']);
        $this->assertSame('#ffffff', $bottom['background_color']);
        $this->assertSame(95, $bottom['background_opacity']);
        $this->assertSame('#64748b', $bottom['icon_color']);
        $this->assertSame('#64748b', $bottom['text_color']);
        $this->assertSame('#020617', $bottom['active_icon_color']);
        $this->assertSame('#020617', $bottom['active_text_color']);
        $this->assertSame('#f1f5f9', $bottom['active_background_color']);
        $this->assertSame(11, $bottom['text_font_size']);
        $this->assertSame(20, $bottom['icon_size']);
        $this->assertSame(72, $bottom['min_height']);
    }

    public function test_bottom_navigation_has_three_built_in_designed_themes(): void
    {
        $settings = app(ClientPortalSettingsService::class);
        $themes = $settings->pwaBottomNavigationThemes()->keyBy('key');

        $this->assertTrue($themes->has('builtin:clinical-blue'));
        $this->assertTrue($themes->has('builtin:slate-professional'));
        $this->assertTrue($themes->has('builtin:emerald-healthcare'));
        $this->assertTrue($themes['builtin:clinical-blue']['builtin']);
        $this->assertSame('#2563eb', $themes['builtin:clinical-blue']['values']['icon_color']);
        $this->assertSame('#eff6ff', $themes['builtin:clinical-blue']['values']['active_background_color']);
        $this->assertSame(22, $themes['builtin:emerald-healthcare']['values']['icon_size']);

        $this->assertTrue($settings->applyPwaBottomNavigationTheme('builtin:clinical-blue', 71));
        $active = $settings->pwaBottomNavigation();
        $this->assertSame('#2563eb', $active['icon_color']);
        $this->assertSame('#1d4ed8', $active['active_icon_color']);
        $this->assertSame('#1e3a8a', $active['active_text_color']);
        $this->assertSame('#eff6ff', $active['active_background_color']);
        $this->assertSame(68, $active['min_height']);
    }

    public function test_bottom_navigation_theme_presets_use_system_settings_and_can_reset(): void
    {
        $settings = app(ClientPortalSettingsService::class);
        $custom = array_replace($settings->pwaBottomNavigationDefaults(), [
            'presentation_style' => 'neumorphism',
            'background_color' => '#112233',
            'text_font_size' => 14,
            'min_height' => 80,
        ]);

        $theme = $settings->savePwaBottomNavigationTheme('Blue Compact', $custom);

        $this->assertSame('clientportal.pwa.bottom_navigation.themes', $theme->group_name);
        $this->assertSame('json', $theme->type);
        $this->assertSame('Blue Compact', $theme->label);
        $this->assertTrue(Setting::query()->whereKey($theme->getKey())->exists());

        $settings->updatePwaBottomNavigation($settings->pwaBottomNavigationDefaults());
        $this->assertTrue($settings->applyPwaBottomNavigationTheme($theme->key));
        $this->assertSame('neumorphism', $settings->pwaBottomNavigation()['presentation_style']);
        $this->assertSame('#112233', $settings->pwaBottomNavigation()['background_color']);
        $this->assertSame(14, $settings->pwaBottomNavigation()['text_font_size']);

        $settings->resetPwaBottomNavigation();
        $this->assertSame($settings->pwaBottomNavigationDefaults(), $settings->pwaBottomNavigation());
    }

    public function test_bottom_navigation_presentation_uses_manifest_defaults_and_safe_overrides(): void
    {
        $registry = app(ApplicationRegistry::class);
        $settings = app(ClientPortalSettingsService::class);
        $application = $registry->find('pharma');

        $defaults = collect($settings->applicationNavigationPresentation($application)['items'])->keyBy('key');
        $this->assertTrue($defaults['overview']['bottom_enabled']);
        $this->assertSame(10, $defaults['overview']['bottom_sort_order']);
        $this->assertSame('home', $defaults['overview']['bottom_icon']);

        $settings->updateApplicationNavigationPresentation('pharma', ['items' => [
            ['key' => 'overview', 'bottom_enabled' => false, 'bottom_sort_order' => 90, 'bottom_icon' => 'squares-2x2'],
            ['key' => 'products', 'bottom_enabled' => true, 'bottom_sort_order' => 5, 'bottom_icon' => 'magnifying-glass'],
        ]], 75);

        $items = collect($settings->applicationNavigationPresentation($application)['items'])->keyBy('key');
        $this->assertFalse($items['overview']['bottom_enabled']);
        $this->assertSame(90, $items['overview']['bottom_sort_order']);
        $this->assertSame('squares-2x2', $items['overview']['bottom_icon']);
        $this->assertTrue($items['products']['bottom_enabled']);
        $this->assertSame(5, $items['products']['bottom_sort_order']);
        $this->assertSame('json', ClientPortalSetting::query()->where('group_name', 'application.pharma.navigation')->where('key', 'items')->value('type'));
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
            'admin.client-apps.pwa.bottom-navigation.update',
            'admin.client-apps.pwa.bottom-navigation.reset',
            'admin.client-apps.pwa.bottom-navigation.themes.store',
            'admin.client-apps.pwa.bottom-navigation.themes.apply',
            'admin.client-apps.pwa.login.update',
            'admin.client-apps.pwa.launcher.edit',
            'admin.client-apps.pwa.launcher.update',
            'admin.client-apps.pwa.applications.update',
            'admin.client-apps.pwa.applications.hub.update',
            'admin.client-apps.pwa.applications.navigation.update',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name);
            $this->assertContains('auth:admin', $route->gatherMiddleware(), $name);
            $this->assertContains('permission:edit_role,admin', $route->gatherMiddleware(), $name);
        }
    }
}

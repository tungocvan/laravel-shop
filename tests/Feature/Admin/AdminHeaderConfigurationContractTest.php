<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHeaderConfigurationContractTest extends TestCase
{
    public function test_header_defaults_define_brand_user_menu_actions_presentation_and_responsive_groups(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));

        foreach (['brand', 'user_menu_config', 'actions', 'presentation', 'responsive'] as $group) {
            $this->assertStringContainsString("'{$group}' => [", $config);
        }

        foreach (['logo_size', 'show_title', 'show_subtitle', 'show_avatar', 'show_name', 'show_email', 'show_role', 'mobile_overflow', 'action_gap', 'backdrop_blur', 'mobile_brand', 'overflow_secondary_actions'] as $key) {
            $this->assertStringContainsString("'{$key}'", $config);
        }
    }

    public function test_layout_manager_normalizes_header_against_bounded_presentation_contract(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString('private function normalizeHeader(', $manager);
        $this->assertStringContainsString("['3.5rem', '4rem', '4.5rem']", $manager);
        $this->assertStringContainsString("['24', '28', '32', '36', '40']", $manager);
        $this->assertStringContainsString("['balanced', 'compact', 'action-heavy']", $manager);
        $this->assertStringContainsString("['system', 'white', 'transparent']", $manager);
        $this->assertStringContainsString("['logo-only', 'logo-title', 'hidden']", $manager);
        $this->assertStringContainsString('private function safePath(', $manager);
        $this->assertStringContainsString('private function nullableString(', $manager);
    }

    public function test_header_contract_preserves_core_search_notifications_and_user_menu_flags(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));

        $this->assertStringContainsString("'search' => true", $config);
        $this->assertStringContainsString("'notifications' => true", $config);
        $this->assertStringContainsString("'user_menu' => true", $config);
        $this->assertStringContainsString("'theme_switcher' => false", $config);
    }

    public function test_dynamic_header_collections_are_data_only_and_runtime_views_remain_server_owned(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));
        $headerService = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));

        $this->assertStringContainsString("'items' => array_values((array) data_get(\$header, 'actions.items', []))", $manager);
        $this->assertStringContainsString("'items' => array_values((array) data_get(\$header, 'user_menu_config.items', []))", $manager);
        $this->assertStringNotContainsString("data_get(\$header, 'view'", $headerService);
    }

    public function test_header_brand_runtime_uses_server_owned_component_and_safe_fallbacks(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderService.php'));
        $brand = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/brand.blade.php'));

        $this->assertStringContainsString("'brand'", $service);
        $this->assertStringContainsString('Admin::livewire.partials.header.components.brand', $service);
        $this->assertStringContainsString('protected function brandContext(', $service);
        $this->assertStringContainsString("config('app.name', 'Admin')", $service);
        $this->assertStringContainsString("\$component['data']['brand']", $brand);
        $this->assertStringContainsString("asset(\$logo)", $brand);
        $this->assertStringContainsString('mb_strtoupper(mb_substr($title, 0, 1))', $brand);
        $this->assertStringContainsString("'hidden sm:flex' => \$mobileBrand === 'hidden'", $brand);
        $this->assertStringContainsString("\$hideTitleOnMobile || \$mobileBrand === 'logo-only'", $brand);
        $this->assertStringContainsString('max-w-44 truncate', $brand);
    }
}

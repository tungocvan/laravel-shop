<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Models\Setting;
use Modules\System\Services\SeoSettingsService;
use Tests\TestCase;

class SystemSeoSettingsTest extends TestCase
{
    public function test_settings_route_and_menu_use_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.index');
        $this->assertNotNull($route);
        $this->assertContains('permission:system.settings.view,admin', $route->gatherMiddleware());

        $menus = json_decode(file_get_contents(base_path('Modules/Admin/data/menus.json')), true, flags: JSON_THROW_ON_ERROR);
        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $settingsMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/settings');
        $this->assertNotNull($settingsMenu);
        $this->assertSame('system.settings.view', $settingsMenu['can']);
    }

    public function test_seo_livewire_enforces_update_permission_and_delegates_service(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/Seo.php'));
        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertStringContainsString("authorizePermission('system.settings.update')", $source);
        $this->assertStringContainsString('SeoSettingsService', $source);
        $this->assertStringNotContainsString('Setting::setValue', $source);
    }

    public function test_seo_preview_is_escaped_and_description_uses_plain_text_input(): void
    {
        $blade = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/partials/seo.blade.php'));
        $this->assertStringNotContainsString('{!! $settings[\'seo_description\']', $blade);
        $this->assertStringNotContainsString('<x-editor', $blade);
        $this->assertStringContainsString('Plain text', $blade);
        $this->assertStringContainsString('wire:confirm', $blade);
    }

    public function test_service_whitelists_keys_strips_description_html_and_forgets_both_cache_namespaces(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/SeoSettingsService.php'));
        foreach (['seo_title', 'seo_description', 'social_facebook', 'social_zalo', 'header_script'] as $key) {
            $this->assertStringContainsString("'{$key}'", $source);
        }
        $this->assertStringContainsString('strip_tags', $source);
        $this->assertStringContainsString("Cache::forget('setting_'.\$key)", $source);
        $this->assertStringContainsString("Cache::forget('wp_opt_'.\$key)", $source);
    }

    public function test_header_script_audit_logs_hash_and_length_not_script_body(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/SeoSettingsService.php'));
        $this->assertStringContainsString('header_script_sha256', $source);
        $this->assertStringContainsString('header_script_length', $source);
        $this->assertStringNotContainsString("'header_script' => \$header", $source);
    }

    public function test_website_runtime_head_still_marks_header_script_as_trusted_raw_configuration(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $runtimeHead = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));

        $this->assertIsString($layout);
        $this->assertIsString($runtimeHead);
        $this->assertStringContainsString("@include('Website::partials.layout.runtime-head')", $layout);
        $this->assertStringContainsString('{!! $headerScript !!}', $runtimeHead);
        $this->assertStringContainsString('Privileged trusted configuration', $runtimeHead);
    }
}

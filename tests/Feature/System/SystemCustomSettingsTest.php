<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemCustomSettingsTest extends TestCase
{
    public function test_settings_route_and_admin_menu_use_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.index');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/settings', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.settings.view,admin', $route->gatherMiddleware());

        $menus = json_decode(
            file_get_contents(base_path('Modules/Admin/data/menus.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $settingsMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/settings');

        $this->assertNotNull($settingsMenu);
        $this->assertSame('system.settings.view', $settingsMenu['can']);
        $this->assertTrue((bool) ($settingsMenu['is_active'] ?? false));
    }

    public function test_custom_component_enforces_update_permission_on_every_mutation(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/Custom.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);

        foreach (['addField', 'deleteField', 'removeGalleryImage', 'save'] as $method) {
            $start = strpos($source, 'function '.$method.'(');
            $this->assertNotFalse($start, "Missing {$method} method.");
            $next = strpos($source, "\n    public function ", $start + 1);
            $methodSource = substr($source, $start, $next === false ? null : $next - $start);
            $this->assertStringContainsString(
                "authorizePermission('system.settings.update')",
                $methodSource,
                "{$method} must authorize update permission.",
            );
        }
    }

    public function test_livewire_delegates_persistence_and_storage_to_service(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/Custom.php'));

        $this->assertStringContainsString('CustomSettingsService', $source);
        $this->assertStringNotContainsString('Setting::create(', $source);
        $this->assertStringNotContainsString('Setting::destroy(', $source);
        $this->assertStringNotContainsString('Storage::disk(', $source);
        $this->assertStringNotContainsString('->store(', $source);
    }

    public function test_upload_policy_rejects_svg_and_bounds_gallery_count(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/Custom.php'));

        $this->assertStringContainsString("'mimes:jpg,jpeg,png,webp'", $source);
        $this->assertStringContainsString("'max:5120'", $source);
        $this->assertStringContainsString("'galleryUploads.*' => ['nullable', 'array', 'max:20']", $source);
        $this->assertStringNotContainsString('svg', strtolower($source));
    }

    public function test_service_scopes_deletes_and_files_to_custom_owned_roots(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/CustomSettingsService.php'));

        $this->assertStringContainsString("->where('group_name', 'custom')", $source);
        $this->assertStringContainsString("private const IMAGE_ROOT = 'settings/custom'", $source);
        $this->assertStringContainsString("private const GALLERY_ROOT = 'settings/gallery'", $source);
        $this->assertStringContainsString("! str_contains(\$normalized, '..')", $source);
        $this->assertStringContainsString('deleteOwnedPath', $source);
    }

    public function test_service_stages_new_files_and_compensates_on_failure(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/CustomSettingsService.php'));

        $storePosition = strpos($source, "->store(self::IMAGE_ROOT, 'public')");
        $transactionPosition = strpos($source, 'DB::transaction(function () use ($settings, $updates)');
        $deleteAfterCommitPosition = strpos($source, 'foreach (array_unique($deleteAfterCommit) as $path)');

        $this->assertNotFalse($storePosition);
        $this->assertNotFalse($transactionPosition);
        $this->assertNotFalse($deleteAfterCommitPosition);
        $this->assertLessThan($transactionPosition, $storePosition);
        $this->assertLessThan($deleteAfterCommitPosition, $transactionPosition);
        $this->assertStringContainsString('foreach ($stagedFiles as $path)', $source);
    }

    public function test_service_invalidates_setting_cache_after_writes(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/CustomSettingsService.php'));

        $this->assertStringContainsString("Cache::forget('setting_'.\$setting->key)", $source);
        $this->assertStringContainsString("Cache::forget('setting_'.\$key)", $source);
    }

    public function test_custom_blade_has_confirmation_loading_errors_and_html_warning(): void
    {
        $source = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/partials/custom.blade.php'));

        $this->assertStringContainsString('wire:confirm=', $source);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $source);
        $this->assertStringContainsString("@error('dynamicImages.'", $source);
        $this->assertStringContainsString("@error('galleryUploads.'", $source);
        $this->assertStringContainsString('Nội dung HTML đặc quyền', $source);
        $this->assertStringContainsString('Không hỗ trợ SVG', $source);
    }
}

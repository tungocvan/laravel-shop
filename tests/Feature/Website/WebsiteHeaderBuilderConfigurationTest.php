<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderPresentationService;
use Tests\TestCase;

class WebsiteHeaderBuilderConfigurationTest extends TestCase
{
    public function test_presentation_service_uses_safe_presets_and_clamps_advanced_values(): void
    {
        $service = app(HeaderPresentationService::class);

        $resolved = $service->resolve([
            'mode' => 'advanced',
            'container' => 'standard',
            'size' => 'normal',
            'background' => 'javascript:bad',
            'custom' => [
                'container_width' => 99999,
                'desktop_height' => 10,
                'tablet_height' => 999,
                'mobile_height' => 64,
                'logo_max_height' => 999,
                'search_max_width' => 1,
            ],
        ]);

        $this->assertSame(1920, $resolved['container_width']);
        $this->assertSame(52, $resolved['heights']['desktop']);
        $this->assertSame(120, $resolved['heights']['tablet']);
        $this->assertSame(64, $resolved['heights']['mobile']);
        $this->assertSame(72, $resolved['custom']['logo_max_height']);
        $this->assertSame(320, $resolved['custom']['search_max_width']);
        $this->assertSame('#ffffff', $resolved['colors']['background']);
    }

    public function test_header_registry_allows_desktop_slot_movement_only_for_registered_components(): void
    {
        $registry = app(HeaderComponentRegistry::class);

        $this->assertSame('Website::components.header.brand', $registry->resolve('brand', 'desktop.main.right')['view']);
        $this->assertSame('Website::components.header.search', $registry->resolve('search', 'desktop.main.left')['view']);

        $this->expectException(\InvalidArgumentException::class);
        $registry->resolve('mobile-menu', 'desktop.main.center');
    }

    public function test_admin_builder_uses_settings_permission_and_safe_schema_persistence(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));

        $this->assertStringContainsString("authorizeAdminPermission('website.settings.manage')", $component);
        $this->assertStringContainsString("'header.layout' => $layout", $component);
        $this->assertStringContainsString("'header.presentation' => $presentation", $component);
        $this->assertStringNotContainsString("'view' =>", $this->extractSaveBuilderSection($component));
        $this->assertStringContainsString('HeaderComponentRegistry $registry', $component);
    }

    public function test_builder_view_exposes_phase_9d_controls_without_drag_drop(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertStringContainsString('Bố cục Header', $view);
        $this->assertStringContainsString('wire:click="saveBuilder"', $view);
        $this->assertStringContainsString('wire:click="resetBuilder"', $view);
        $this->assertStringContainsString('moveComponent(', $view);
        $this->assertStringContainsString('presentation.mode', $view);
        $this->assertStringContainsString('presentation.custom.desktop_height', $view);
        $this->assertStringNotContainsString('sortable', strtolower($view));
        $this->assertStringNotContainsString('draggable=', strtolower($view));
    }

    public function test_storefront_reads_persisted_builder_settings_through_services(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/header.blade.php'));

        $this->assertStringContainsString("get('header.layout')", $provider);
        $this->assertStringContainsString("get('header.presentation')", $provider);
        $this->assertStringContainsString('HeaderPresentationService::class', $provider);
        $this->assertStringContainsString('--header-height-desktop', $header);
        $this->assertStringContainsString('--header-search-max', $header);
        $this->assertStringContainsString('--header-logo-max', $header);
    }

    private function extractSaveBuilderSection(string $component): string
    {
        $start = strpos($component, 'public function saveBuilder');
        $end = strpos($component, 'public function resetBuilder');

        return substr($component, $start ?: 0, ($end ?: strlen($component)) - ($start ?: 0));
    }
}

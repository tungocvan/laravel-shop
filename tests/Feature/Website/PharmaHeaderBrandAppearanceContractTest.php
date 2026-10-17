<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaBrandAppearanceService;
use Tests\TestCase;

final class PharmaHeaderBrandAppearanceContractTest extends TestCase
{
    public function test_legacy_defaults_and_per_device_brand_parts_are_safe(): void
    {
        $service = new PharmaBrandAppearanceService;
        $defaults = $service->resolve(null);
        $this->assertSame('Danh mục và thông tin dược phẩm', $defaults['slogan']);
        $this->assertTrue($service->visible($defaults, 'logo', 'desktop'));
        $this->assertTrue($service->visible($defaults, 'logo', 'mobile'));

        $values = $service->resolve([
            'slogan' => 'Chuyển đổi số doanh nghiệp',
            'visibility' => ['logo' => 'desktop', 'name' => 'mobile', 'slogan' => 'hidden'],
            'typography' => ['desktop' => ['name' => ['font_size' => 999, 'font_family' => 'url(javascript:alert(1))', 'color' => 'red;display:none']]],
        ]);
        $this->assertSame('Chuyển đổi số doanh nghiệp', $values['slogan']);
        $this->assertFalse($service->visible($values, 'logo', 'mobile'));
        $this->assertTrue($service->visible($values, 'name', 'mobile'));
        $this->assertFalse($service->visible($values, 'slogan', 'desktop'));
        $this->assertSame(32, $values['typography']['desktop']['name']['font_size']);
        $this->assertSame('inherit', $values['typography']['desktop']['name']['font_family']);
        $this->assertSame('#0f172a', $values['typography']['desktop']['name']['color']);
    }

    public function test_admin_persistence_builder_visibility_and_frontend_rendering_are_wired(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('public function saveBrandAppearance(', $hub);
        $this->assertStringContainsString('public function resetBrandAppearance(', $hub);
        $this->assertStringContainsString('website.pharma_header.brand_appearance', $hub);
        $this->assertStringContainsString("'brand_appearance' =>", $hub);
        $this->assertStringContainsString('wire:click="saveBrandAppearance"', $admin);
        $this->assertStringContainsString('brandAppearance.visibility.{{ $part }}', $admin);
        $this->assertStringContainsString('brandAppearance.typography.{{ $device }}.{{ $part }}.font_size', $admin);
        $this->assertStringContainsString('brandAppearance.slogan', $admin);
        $this->assertStringContainsString('PharmaBrandAppearanceService::class', $frontend);
        $this->assertStringContainsString("['visibility']['logo']", $frontend);
        $this->assertStringContainsString("['visibility']['name']", $frontend);
        $this->assertStringContainsString("['visibility']['slogan']", $frontend);
        $this->assertStringNotContainsString('>Danh mục và thông tin dược phẩm</span>', $frontend);
    }
}

<?php

namespace Modules\Website\Tests\Feature;

use Modules\Website\Services\PharmaProductCardStyle;
use Tests\TestCase;

class PharmaProductCardBuilderTest extends TestCase
{
    public function test_style_normalization_rejects_invalid_values(): void
    {
        $style = app(PharmaProductCardStyle::class)->normalize([
            'preset' => 'invalid', 'background' => 'red;position:absolute',
            'desktop_columns' => 99, 'radius' => -20, 'title_lines' => 100,
            'action_label' => '<script>alert(1)</script>',
        ]);
        $this->assertSame('classic', $style['preset']);
        $this->assertSame('#ffffff', $style['background']);
        $this->assertSame(4, $style['desktop_columns']);
        $this->assertSame(0, $style['radius']);
        $this->assertSame(4, $style['title_lines']);
    }

    public function test_admin_and_storefront_share_card_component_and_save_contract(): void
    {
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));
        $public = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-product-section.blade.php'));
        $controller = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $this->assertStringContainsString("Website::partials.pharma-product-card", $admin);
        $this->assertStringContainsString("Website::partials.pharma-product-card", $public);
        $this->assertStringContainsString("'card_design' => app(", $controller);
        $this->assertStringContainsString("wire:model.live=\"pharmaProductSections.", $admin);
    }
    public function test_responsive_grid_uses_css_breakpoints_without_inline_column_override(): void
    {
        $blade = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/pharma-product-section.blade.php'));
        $this->assertStringContainsString('grid-template-columns:repeat(var(--pharma-product-cols),minmax(0,1fr))', $blade);
        $this->assertStringContainsString('[data-product-card-grid][data-mobile="1"]', $blade);
        $this->assertStringContainsString('[data-product-card-grid][data-tablet="2"]', $blade);
        $this->assertStringContainsString('[data-product-card-grid][data-desktop="4"]', $blade);
        $this->assertStringNotContainsString('--pharma-product-cols:{{ $cardDesign', $blade);
        $this->assertStringNotContainsString('attr(data-tablet', $blade);
    }


}

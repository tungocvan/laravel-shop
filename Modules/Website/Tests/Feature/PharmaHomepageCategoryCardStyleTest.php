<?php

namespace Modules\Website\Tests\Feature;

use Modules\Website\Services\PharmaCategoryCardStyle;
use Tests\TestCase;

class PharmaHomepageCategoryCardStyleTest extends TestCase
{
    public function test_card_style_normalization_preserves_defaults_and_sanitizes_input(): void
    {
        $service = new PharmaCategoryCardStyle();
        $this->assertSame('clinical', $service->normalize([])['preset']);
        $result = $service->normalize([
            'preset' => 'soft', 'font_size' => 999, 'radius' => -5,
            'padding' => 17, 'accent' => '#AABBCC', 'background' => 'red;evil',
            'show_icon' => false, 'mobile_columns' => 99,
        ]);
        $this->assertSame('soft', $result['preset']);
        $this->assertSame(20, $result['font_size']);
        $this->assertSame(0, $result['radius']);
        $this->assertSame(17, $result['padding']);
        $this->assertSame('#aabbcc', $result['accent']);
        $this->assertSame('#ffffff', $result['background']);
        $this->assertFalse($result['show_icon']);
        $this->assertSame(2, $result['mobile_columns']);
        $this->assertSame('medical', $service->normalize(['icon' => '<script>'])['icon']);
        $this->assertSame('pill', $service->normalize(['icon' => 'pill'])['icon']);
        $this->assertStringNotContainsString('evil', $service->css($result));
        $contrast = $service->normalize(['icon_color'=>'#047857','icon_background'=>'#047857']);
        $this->assertNotSame($contrast['icon_color'], $contrast['icon_background']);
        $this->assertStringContainsString('--category-icon-bg:', $service->css($contrast));
        $this->assertSame('#ffffff', $service->normalize(['preset'=>'premium','accent'=>'#047857'])['control_foreground']);
        $this->assertSame('#064e3b', $service->normalize(['preset'=>'premium','accent'=>'#ffffff'])['control_foreground']);
        $this->assertStringContainsString('--category-control-fg:', $service->css(['preset'=>'premium']));
    }

    public function test_homepage_uses_saved_card_design_only_when_present(): void
    {
        $source = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));
        $this->assertStringContainsString("isset(\$categoryDisplay['card_design'])", $source);
        $this->assertStringContainsString('pharma-category-card-custom', $source);
        $this->assertStringContainsString('pharma-category-grid-custom', $source);
    }

    public function test_category_editor_is_modal_and_saves_through_existing_settings_key(): void
    {
        $blade = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));
        $livewire = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $this->assertStringContainsString('aria-modal="true"', $blade);
        $this->assertStringContainsString(" ? 'flex' : 'hidden' ", $blade);
        $this->assertStringNotContainsString("@endif\n</section>", $blade);
        $this->assertStringNotContainsString("</section>\n        </div>\n    @endif\n</section>", $blade);
        $this->assertStringContainsString('pharma-category-card-preview', $blade);
        $this->assertStringContainsString("'website.pharma_homepage.categories'", $livewire);
        $this->assertStringContainsString("['card_design']", $livewire);
        $this->assertStringContainsString("\$validated['pharmaCategoryDisplay']['card_design'] = app(PharmaCategoryCardStyle::class)->normalize(\$this->pharmaCategoryCard)", $livewire);
        $this->assertStringContainsString('pharmaCategoryCard.icon', $blade);
        $this->assertStringContainsString('selectedCategoryIconId', $blade);
        $this->assertStringContainsString('selectedCategoryIconValue', $blade);
        $this->assertStringContainsString("\$preview['icons_by_category'][\$previewCategoryId] ?? \$preview['icon']", $blade);
        $this->assertStringContainsString("\$selectedCategoryIconValue !== ''", $blade);
        $this->assertStringContainsString('previewCategoryIds', $blade);
        $this->assertStringContainsString('cardIcon', $blade);
        $this->assertStringContainsString('icon_background', $blade);
        $this->assertStringContainsString('updatedSelectedCategoryIconValue', $livewire);
        $this->assertStringContainsString("partials.pharma-category-card-v2", $blade);
        $this->assertStringContainsString("partials.pharma-category-card-v2", file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php')));
    }
}

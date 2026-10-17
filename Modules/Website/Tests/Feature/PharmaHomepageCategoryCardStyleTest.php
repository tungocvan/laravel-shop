<?php

namespace Modules\Website\Tests\Feature;

use Modules\Website\Services\PharmaCategoryCardStyle;
use Modules\Website\Livewire\Admin\Home\HomeSettings;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
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

    public function test_pharma_modal_blade_compiles_to_valid_php(): void
    {
        $path = base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php');
        $compiled = Blade::compileString(file_get_contents($path));
        $temporaryFile = tempnam(sys_get_temp_dir(), 'pharma-blade-');
        try {
            file_put_contents($temporaryFile, $compiled);
            $process = new Process([PHP_BINARY, '-l', $temporaryFile]);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_category_preview_uses_actual_category_icon_overrides(): void
    {
        $component = new HomeSettings();
        $component->pharmaCategoryCard = ['icon' => 'medical', 'icons_by_category' => [12 => 'heart']];
        $component->pharmaCategoryIconChoices = [12 => 'Dược phẩm', 20 => 'Thuốc khác'];
        $component->selectedCategoryIconId = '20';

        $items = $component->categoryCardPreviewItems();
        $this->assertSame('Thuốc khác', $items[0]['name']);
        $this->assertSame('medical', $items[0]['icon']);
        $this->assertSame('Dược phẩm', $items[1]['name']);
        $this->assertSame('heart', $items[1]['icon']);
    }

    public function test_category_editor_is_modal_and_saves_through_existing_settings_key(): void
    {
        $blade = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));
        $livewire = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $this->assertStringContainsString('aria-modal="true"', $blade);
        $this->assertStringContainsString('pharma-category-card-preview', $blade);
        $this->assertStringContainsString("'website.pharma_homepage.categories'", $livewire);
        $this->assertStringContainsString("['card_design']", $livewire);
        $this->assertStringContainsString("\$validated['pharmaCategoryDisplay']['card_design'] = app(PharmaCategoryCardStyle::class)->normalize(\$this->pharmaCategoryCard)", $livewire);
        $this->assertStringContainsString('pharmaCategoryCard.icon', $blade);
        $this->assertStringContainsString('selectedCategoryIconId', $blade);
        $this->assertStringContainsString('selectedCategoryIconValue', $blade);
        $this->assertStringContainsString('icon_background', $blade);
        $this->assertStringContainsString('updatedSelectedCategoryIconValue', $livewire);
        $this->assertStringContainsString("partials.pharma-category-card-v2", $blade);
        $this->assertStringContainsString("partials.pharma-category-card-v2", file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php')));
    }
}

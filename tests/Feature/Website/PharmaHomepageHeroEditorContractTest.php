<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaHeroService;
use Tests\TestCase;

final class PharmaHomepageHeroEditorContractTest extends TestCase
{
    public function test_hero_defaults_and_untrusted_inputs_are_normalized(): void
    {
        $service = new PharmaHeroService;
        $defaults = $service->normalize(null);
        $this->assertSame('Khám phá danh mục sản phẩm dược phẩm', $defaults['title']);
        $this->assertSame('/product', $defaults['button_url']);
        $this->assertTrue($defaults['show_illustration']);
        $changed = $service->normalize([
            'title' => '<script>unsafe</script>Tra cứu thuốc',
            'button_url' => 'javascript:alert(1)',
            'background_start' => 'red;position:absolute',
            'desktop_title_size' => 900,
            'mobile_padding' => -50,
            'show_button' => false,
        ]);
        $this->assertSame('unsafeTra cứu thuốc', $changed['title']);
        $this->assertSame('/product', $changed['button_url']);
        $this->assertSame('#073b48', $changed['background_start']);
        $this->assertSame(80, $changed['desktop_title_size']);
        $this->assertSame(0, $changed['mobile_padding']);
        $this->assertFalse($changed['show_button']);
    }

    public function test_homepage_workspace_save_and_public_hero_are_connected(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $tabs = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-hero-editor.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString("Route::get('/homepage-settings'", $routes);
        $this->assertStringContainsString("website.pharma_hero", $hub);
        $this->assertStringContainsString('public function savePharmaHero(', $hub);
        $this->assertStringContainsString('public function resetPharmaHero(', $hub);
        $this->assertStringContainsString("authorizeAdminPermission('website.home.manage')", $hub);
        $this->assertStringContainsString("'hero' => 'Hero Banner Pharma'", $tabs);
        $this->assertStringContainsString('pharma-hero-editor', $tabs);
        $this->assertStringContainsString('wire:click="savePharmaHero"', $editor);
        $this->assertStringContainsString("'title' => 'Tiêu đề chính'", $editor);
        $this->assertStringContainsString('wire:model.live.debounce.400ms="pharmaHero.{{ $field }}"', $editor);
        $this->assertStringContainsString('PharmaHeroService::class', $frontend);
        $this->assertStringContainsString("['mobile_title_size']", $frontend);
        $this->assertStringContainsString("['desktop_title_size']", $frontend);
        $this->assertStringContainsString("['show_illustration']", $frontend);
    }
}

<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\BannerThemeLibrary;
use Modules\Website\Services\PharmaHeroService;
use Tests\TestCase;

final class PharmaBannerThemeLibraryContractTest extends TestCase
{
    public function test_presets_are_valid_and_do_not_replace_hero_content_or_images(): void
    {
        $hero = new PharmaHeroService;
        $library = new BannerThemeLibrary;
        $this->assertCount(4, $library->all());

        $current = $hero->normalize([
            'title' => 'Tiêu đề tùy chỉnh',
            'description' => 'Mô tả đã lưu',
            'caption' => 'Chú thích riêng',
            'button_text' => 'Xem thêm',
            'button_url' => '/product',
            'illustration_type' => 'image',
            'illustration_image' => 'pharma/hero/custom.webp',
            'desktop_width' => 1440,
            'show_button' => false,
        ]);
        foreach (array_keys($library->all()) as $id) {
            $applied = $library->apply($id, $current, $hero);
            $this->assertSame($current['title'], $applied['title']);
            $this->assertSame($current['description'], $applied['description']);
            $this->assertSame($current['caption'], $applied['caption']);
            $this->assertSame($current['button_text'], $applied['button_text']);
            $this->assertSame($current['illustration_image'], $applied['illustration_image']);
            $this->assertSame($current['desktop_width'], $applied['desktop_width']);
            $this->assertFalse($applied['show_button']);
            $this->assertSame($applied, $hero->normalize($applied));
        }
        $this->assertNull($library->apply('unknown-theme', $current, $hero));
    }

    public function test_banner_library_and_homepage_editor_share_the_same_presets(): void
    {
        $banner = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/banner/banner-manager.blade.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-hero-editor.blade.php'));
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $this->assertStringContainsString('BannerThemeLibrary::class', $banner);
        $this->assertStringContainsString('BannerThemeLibrary::class', $editor);
        $this->assertStringContainsString('wire:click="applyPharmaHeroTheme"', $editor);
        $this->assertStringContainsString('public function applyPharmaHeroTheme(', $hub);
        $this->assertStringContainsString("authorizeAdminPermission('website.home.manage')", $hub);
        $this->assertStringContainsString('Lưu Hero Banner', $editor);
    }
}

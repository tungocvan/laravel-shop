<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\HeaderPresentationService;
use Tests\TestCase;

final class PharmaHeaderMenuAppearanceContractTest extends TestCase
{
    public function test_menu_appearance_is_normalized_per_device_and_rejects_unsafe_css(): void
    {
        $service = new HeaderPresentationService;
        $presentation = $service->resolve([
            'menu_style' => [
                'primary' => [
                    'font_family' => 'url(javascript:alert(1))',
                    'font_size' => 999,
                    'font_weight' => '900',
                    'item_gap' => -50,
                    'hover_color' => 'red;position:fixed',
                    'hover_effect' => 'script',
                ],
                'mobile' => [
                    'font_family' => 'Georgia, serif',
                    'font_size' => 16,
                    'item_gap' => 8,
                ],
            ],
        ]);

        $desktop = $presentation['menu_style']['primary'];
        $mobile = $presentation['menu_style']['mobile'];

        $this->assertSame('inherit', $desktop['font_family']);
        $this->assertSame(22, $desktop['font_size']);
        $this->assertSame('600', $desktop['font_weight']);
        $this->assertSame(0, $desktop['item_gap']);
        $this->assertSame('#047857', $desktop['hover_color']);
        $this->assertSame('underline', $desktop['hover_effect']);
        $this->assertSame('Georgia, serif', $mobile['font_family']);
        $this->assertSame('600', $mobile['font_weight']); // Missing key must resolve safely.
        $this->assertSame(16, $mobile['font_size']);
        $this->assertSame(8, $mobile['item_gap']);
        $this->assertSame(20, $service->resolve([])['menu_style']['primary']['item_gap']);
        $this->assertSame(0, $service->resolve([])['menu_style']['mobile']['item_gap']);
    }

    public function test_menu_editor_and_frontend_share_presentation_and_theme_snapshot(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $blade = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString('public function saveMenuAppearance(', $hub);
        $this->assertStringContainsString("settingKey('presentation')", $hub);
        $this->assertStringContainsString('public function resetMenuAppearance(', $hub);
        $this->assertStringContainsString("'presentation' => \$presentationService->resolve(\$this->presentation)", $hub);
        $this->assertStringContainsString('wire:click="saveMenuAppearance"', $blade);
        $this->assertStringContainsString('wire:click="resetMenuAppearance"', $blade);
        $this->assertStringContainsString('presentation.menu_style.{{ $device }}.font_family', $blade);
        $this->assertStringContainsString('presentation.menu_style.{{ $device }}.font_size', $blade);
        $this->assertStringContainsString("['menu_style']['primary']", $frontend);
        $this->assertStringContainsString("['menu_style']['mobile']", $frontend);
        $this->assertStringContainsString("['hover_effect']", $frontend);
    }
}

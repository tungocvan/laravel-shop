<?php

namespace Modules\Website\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FooterSettingsUxContractTest extends TestCase
{
    public function test_footer_admin_keeps_legacy_editors_accessible_but_defaults_to_builder(): void
    {
        $source = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/footer/index.blade.php'));

        $this->assertStringContainsString("activeTab: 'builder'", $source);
        $this->assertStringContainsString('showLegacy: false', $source);
        foreach (['footer-info', 'footer-columns', 'social-links', 'footer-settings-hub'] as $component) {
            $this->assertStringContainsString($component, $source);
        }
    }

    public function test_footer_hub_separates_layout_preview_and_presentation_without_changing_save_contract(): void
    {
        $source = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        foreach (["footerPanel === 'layout'", "footerPanel === 'preview'", "footerPanel === 'style'"] as $panel) {
            $this->assertStringContainsString($panel, $source);
        }
        $this->assertStringContainsString('wire:click="saveBuilder"', $source);
        $this->assertStringContainsString('wire:click="resetBuilder"', $source);
        $this->assertStringContainsString("partials.builder-preview", $source);
        $this->assertStringContainsString('wire:model.live="editingTemplate"', $source);
    }

    public function test_column_draft_is_isolated_from_published_layout_and_has_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        $this->assertStringContainsString("settingKey('columns')", $component);
        $this->assertStringContainsString('function saveFooterColumns', $component);
        $this->assertStringContainsString('function addFooterColumn', $component);
        $this->assertStringContainsString('function removeFooterColumn', $component);
        $this->assertStringContainsString('function moveFooterColumn', $component);
        $this->assertStringContainsString('wire:click="saveFooterColumns"', $view);
        $this->assertStringContainsString('Chưa áp dụng ra Footer công khai', $view);
    }

    public function test_footer_has_built_in_theme_gallery_independent_of_saved_themes(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('function footerThemePresets()', $component);
        $this->assertStringContainsString('function applyFooterThemePreset(', $component);
        foreach (['midnight', 'emerald', 'ocean', 'charcoal', 'light'] as $preset) {
            $this->assertStringContainsString("'".$preset."' =>", $component);
        }
        $this->assertStringContainsString('wire:click="applyFooterThemePreset(', $view);
        $this->assertStringContainsString('Thư viện giao diện Footer', $view);
        $this->assertStringContainsString('Đang chọn', $view);
        $this->assertStringContainsString("'accent' => false", $component);
        $this->assertStringContainsString("'border' => false", $component);
    }

    public function test_pwa_footer_card_settings_and_install_guide_are_configurable(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterInfo.php'));
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php'));
        $card = file_get_contents(base_path('Modules/Website/resources/views/components/footer/app-install.blade.php'));
        $installer = file_get_contents(base_path('Modules/Website/resources/views/partials/pwa-installer.blade.php'));

        foreach (['app_card_background', 'app_card_text', 'app_card_muted', 'app_card_shadow', 'app_guide_label', 'app_guide_title', 'app_guide_ios', 'app_guide_android', 'app_guide_other'] as $key) {
            $this->assertStringContainsString("'footer.".$key."'", $component);
            $this->assertStringContainsString('wire:model="'.$key.'"', $admin);
            $this->assertStringContainsString("'footer.".$key."'", $card);
        }
        $this->assertStringContainsString('data-pwa-guide-open', $installer);
        $this->assertStringContainsString('data-pwa-android-guide', $installer);
        $this->assertStringContainsString("guideButton?.addEventListener('click'", $installer);
    }

    public function test_footer_admin_blade_compiles_without_php_parse_errors(): void
    {
        foreach ([
            'Modules/Website/resources/views/pages/admin/footer/index.blade.php',
            'Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php',
        ] as $path) {
            $compiled = Blade::compileString(file_get_contents(base_path($path)));
            $temporary = tempnam(sys_get_temp_dir(), 'footer-blade-');

            try {
                file_put_contents($temporary, $compiled);
                $process = new Process([PHP_BINARY, '-l', $temporary]);
                $process->run();
                $this->assertTrue($process->isSuccessful(), $path.': '.$process->getErrorOutput().$process->getOutput());
            } finally {
                @unlink($temporary);
            }
        }
    }
    public function test_column_draft_supports_ordered_text_and_links_without_publishing(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        foreach (['addFooterColumnItem', 'removeFooterColumnItem', 'moveFooterColumnItem'] as $method) {
            $this->assertStringContainsString('function '.$method, $component);
            $this->assertStringContainsString($method, $view);
        }
        $this->assertStringContainsString('function normalizeFooterColumnItems', $component);
        $this->assertStringContainsString("['text', 'link']", $component);
        $this->assertStringContainsString("settingKey('columns')", $component);
        $this->assertStringContainsString('Chưa áp dụng ra Footer công khai', $view);
    }

}

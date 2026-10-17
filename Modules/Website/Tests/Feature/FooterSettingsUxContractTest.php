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
}    public function test_column_draft_supports_ordered_text_and_links_without_publishing(): void
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
}

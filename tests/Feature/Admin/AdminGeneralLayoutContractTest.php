<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminGeneralLayoutContractTest extends TestCase
{
    public function test_general_layout_defaults_expose_bounded_spacing_surface_and_behavior_tokens(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));

        foreach (['content_padding_x', 'content_padding_top', 'content_padding_bottom', 'section_gap', 'tablet_padding_x', 'mobile_padding_x'] as $key) {
            $this->assertStringContainsString("'{$key}'", $config);
        }

        foreach (['page_background', 'content_surface', 'border', 'radius', 'reduced_motion'] as $key) {
            $this->assertStringContainsString("'{$key}'", $config);
        }
    }

    public function test_layout_manager_normalizes_general_tokens_against_safe_scales(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString("private const SPACING_SCALE = ['0', '1', '2', '3', '4', '5', '6', '8', '10', '12']", $manager);
        $this->assertStringContainsString("['system', 'white', 'slate-50']", $manager);
        $this->assertStringContainsString("['transparent', 'system', 'white']", $manager);
        $this->assertStringContainsString("['system', 'none']", $manager);
        $this->assertStringContainsString("['none', 'sm', 'md', 'lg']", $manager);
        $this->assertStringContainsString('private function spacing(', $manager);
    }

    public function test_general_settings_validation_and_reset_include_new_layout_tokens(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertStringContainsString("\$spacing = 'required|in:0,1,2,3,4,5,6,8,10,12'", $component);
        $this->assertStringContainsString("'config.layout.spacing.content_padding_x' => \$spacing", $component);
        $this->assertStringContainsString("'config.layout.surface.page_background'", $component);
        $this->assertStringContainsString("'config.layout.behavior.reduced_motion' => 'boolean'", $component);
        $this->assertStringContainsString("'spacing' => data_get(\$config, 'layout.spacing', [])", $component);
        $this->assertStringContainsString("'surface' => data_get(\$config, 'layout.surface', [])", $component);
        $this->assertStringContainsString("'behavior' => data_get(\$config, 'layout.behavior', [])", $component);
    }
}

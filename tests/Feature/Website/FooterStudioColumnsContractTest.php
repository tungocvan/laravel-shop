<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioColumnsContractTest extends TestCase
{
    public function test_columns_and_advanced_layout_share_one_studio_panel(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString("'layout' => 'Thiết kế'", $view);
        $this->assertStringContainsString('Bố cục Legacy', $view);
        $this->assertStringContainsString('footerColumns.{{ $index }}.module', $view);
        $this->assertStringContainsString('footerColumns.{{ $index }}.desktop', $view);
        $this->assertStringContainsString('footerColumns.{{ $index }}.mobile', $view);
    }

    public function test_preview_reads_live_column_visibility_and_responsive_module(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $this->assertStringContainsString("column['enabled']", $preview);
        $this->assertStringContainsString("column['desktop']", $preview);
        $this->assertStringContainsString("column['mobile']", $preview);
        $this->assertStringContainsString("column['module']", $preview);
        $this->assertStringContainsString("column['width']", $preview);
    }

    public function test_profile_snapshot_persists_columns_without_global_database_mutation(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $resolver = file_get_contents(base_path('Modules/Website/Services/FooterProfileResolver.php'));
        $this->assertStringContainsString('public function updatedFooterColumns()', $component);
        $this->assertStringContainsString("\$this->columnsSource = 'profile';", $component);
        $this->assertStringContainsString("'module' =>", $component);
        $this->assertStringContainsString("'module' =>", $resolver);
        $this->assertStringContainsString("'desktop' =>", $resolver);
        $this->assertStringContainsString("'mobile' =>", $resolver);
    }
}

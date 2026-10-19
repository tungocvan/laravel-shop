<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioSlugImportContractTest extends TestCase
{
    public function test_footer_column_editor_offers_existing_menu_slug_import(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString("=== 'menu-links'", $view);
        $this->assertStringContainsString('selectedLegacyFooterSlugs.', $view);
        $this->assertStringContainsString('importLegacyFooterColumn(', $view);
        $this->assertStringContainsString('Nạp slug', $view);
    }

    public function test_slug_import_is_draft_only_and_preserves_legacy_database(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $start = strpos($source, 'public function importLegacyFooterColumn(');
        $end = strpos($source, 'public function addFooterColumnItem(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($source, $start, $end - $start);
        $this->assertStringContainsString("where('slug', \$slug)", $method);
        $this->assertStringContainsString("where('is_active', true)", $method);
        $this->assertStringContainsString("->take(12)", $method);
        $this->assertStringContainsString("\$this->footerColumns[\$index]['items']", $method);
        $this->assertStringNotContainsString('updateMany(', $method);
        $this->assertStringNotContainsString('->save(', $method);
    }
}

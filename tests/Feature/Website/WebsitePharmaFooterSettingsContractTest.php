<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaFooterSettingsContractTest extends TestCase
{
    #[Test]
    public function pharma_footer_has_independent_template_selection_and_editable_content(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $this->assertStringContainsString("get('website.public_template', 'default')", $hub);
        $this->assertStringContainsString('updatedEditingTemplate', $hub);
        $this->assertStringContainsString('savePharmaContent', $hub);
        $this->assertStringContainsString('website.pharma_footer.content', $hub);
        $this->assertStringContainsString("editingTemplate !== 'pharma'", $hub);

        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('Template đang quản trị', $editor);
        $this->assertStringContainsString('wire:model.live="editingTemplate"', $editor);
        $this->assertStringContainsString('wire:click="savePharmaContent"', $editor);

        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/footer.blade.php'));
        $this->assertStringContainsString('website.pharma_footer.content', $frontend);
        $this->assertStringContainsString("content['links']", $frontend);
        $this->assertStringContainsString('FILTER_VALIDATE_URL', $frontend);

        $default = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $this->assertStringNotContainsString('website.pharma_footer.content', $default);
    }
}

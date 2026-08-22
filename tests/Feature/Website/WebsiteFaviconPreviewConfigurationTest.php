<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFaviconPreviewConfigurationTest extends TestCase
{
    public function test_livewire_allows_ico_temporary_preview_for_website_favicon(): void
    {
        $config = config('livewire.temporary_file_upload.preview_mimes', []);
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));

        $this->assertContains('ico', $config);
        $this->assertStringContainsString('wire:model="newFavicon"', $view);
        $this->assertStringContainsString('$newFavicon->temporaryUrl()', $view);
        $this->assertStringContainsString('.ico', $view);
    }
}

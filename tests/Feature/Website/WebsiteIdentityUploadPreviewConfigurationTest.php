<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteIdentityUploadPreviewConfigurationTest extends TestCase
{
    public function test_identity_settings_show_current_and_temporary_logo_favicon_previews(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/settings/website-settings.blade.php'));

        $this->assertStringContainsString('$newLogo->temporaryUrl()', $view);
        $this->assertStringContainsString('$newFavicon->temporaryUrl()', $view);
        $this->assertStringContainsString("asset('storage/'.\$logo)", $view);
        $this->assertStringContainsString("asset('storage/'.\$favicon)", $view);
        $this->assertStringContainsString('File Logo đã được nhận', $view);
        $this->assertStringContainsString('File Favicon đã được nhận', $view);
        $this->assertStringContainsString('wire:loading wire:target="newLogo"', $view);
        $this->assertStringContainsString('wire:loading wire:target="newFavicon"', $view);
    }
}

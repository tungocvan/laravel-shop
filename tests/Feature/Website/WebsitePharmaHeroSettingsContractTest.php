<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaHeroSettingsContractTest extends TestCase
{
    #[Test]
    public function homepage_builder_embeds_permission_gated_pharma_editor(): void
    {
        $builder = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/PharmaHeroSettings.php'));

        $this->assertStringContainsString("@livewire('website.admin.home.pharma-hero-settings')", $builder);
        $this->assertStringContainsString("authorizeAdminPermission('website.home.manage')", $component);
        $this->assertStringContainsString("get('website.pharma.hero'", $component);
        $this->assertStringContainsString("set('website.pharma.hero'", $component);
        $this->assertStringContainsString("'heroImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072'", $component);
    }

    #[Test]
    public function pharma_home_uses_escaped_settings_and_keeps_product_route(): void
    {
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/pharma.blade.php'));

        $this->assertStringContainsString("get('website.pharma.hero'", $home);
        $this->assertStringContainsString("{{ \$heroContent['title'] }}", $home);
        $this->assertStringContainsString("{{ \$heroContent['description'] }}", $home);
        $this->assertStringContainsString("{{ \$heroContent['button'] }}", $home);
        $this->assertStringContainsString("route('product.list')", $home);
        $this->assertStringContainsString("Storage::disk('public')->url(", $home);
    }
}

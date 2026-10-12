<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteTemplateSelectorContractTest extends TestCase
{
    #[Test]
    public function admin_settings_embeds_template_selector(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/settings/index.blade.php'));
        $this->assertStringContainsString('website.admin.settings.website-template-selector', $view);
    }

    #[Test]
    public function selector_requires_admin_permission_and_valid_template(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Settings/WebsiteTemplateSelector.php'));
        $this->assertStringContainsString("authorizeAdminPermission('website.settings.manage')", $component);
        $this->assertStringContainsString('required|in:default,pharma', $component);
        $this->assertStringContainsString("settings->set('website.template'", $component);
    }
}

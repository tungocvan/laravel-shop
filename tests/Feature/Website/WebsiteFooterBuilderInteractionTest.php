<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterBuilderInteractionTest extends TestCase
{
    public function test_footer_builder_exposes_device_filters_drag_drop_and_responsive_preview(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString("builderDevice: 'desktop'", $view);
        $this->assertStringContainsString("previewDevice: 'desktop'", $view);
        $this->assertStringContainsString("builderDevice = 'mobile'", $view);
        $this->assertStringContainsString('draggable="true"', $view);
        $this->assertStringContainsString('moveComponentByDrag', $view);
        $this->assertStringContainsString('Responsive Preview', $preview);
        $this->assertStringContainsString("previewDevice = 'desktop'", $preview);
        $this->assertStringContainsString("previewDevice = 'mobile'", $preview);
        $this->assertStringContainsString('builderSlots', $preview);
        $this->assertStringNotContainsString('Sortable.', $view);
    }

    public function test_footer_drag_drop_is_validated_server_side_through_registry(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));

        $this->assertStringContainsString('function moveComponentByDrag(', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.footer.manage')", $component);
        $this->assertStringContainsString('$registry->resolve(', $component);
        $this->assertStringContainsString("addError('builder'", $component);
        $this->assertStringContainsString("'previewPresentation'", $component);
    }
}

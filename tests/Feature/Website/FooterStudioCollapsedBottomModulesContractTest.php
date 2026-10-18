<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioCollapsedBottomModulesContractTest extends TestCase
{
    public function test_columns_and_bottom_bar_are_collapsed_by_default(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $this->assertStringContainsString('<details wire:key="footer-column-editor-', $view);
        $this->assertStringContainsString('Thanh cuối Footer · Bottom Bar', $view);
        $this->assertStringContainsString('aria-label="Thiết kế thanh cuối Footer"', $view);
        $this->assertStringContainsString('wire:click="addBottomModule(', $view);
        $this->assertStringContainsString('wire:click="removeBottomModule(', $view);
        $this->assertStringNotContainsString('<details wire:key="footer-column-editor-{{ $index }}" open', $view);
    }

    public function test_bottom_modules_use_registry_and_profile_layout_without_database_writes(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $start = strpos($hub, 'public function addBottomModule(');
        $end = strpos($hub, 'public function toggleComponent(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $methods = substr($hub, $start, $end - $start);
        $this->assertStringContainsString('$registry->resolve($type, $slot)', $methods);
        $this->assertStringContainsString('$this->builderSlots[$slot][]', $methods);
        $this->assertStringNotContainsString('updateMany(', $methods);
        $this->assertStringNotContainsString('->save(', $methods);

        $config = file_get_contents(base_path('Modules/Website/Config/footer.php'));
        $this->assertStringContainsString("'desktop.bottom.left', 'desktop.bottom.right', 'mobile.bottom'", $config);
    }

    public function test_preview_has_no_forced_desktop_horizontal_min_width(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $this->assertStringContainsString('overflow-hidden rounded-xl', $view);
        $this->assertStringNotContainsString('min-w-[560px]', $view);
    }
}

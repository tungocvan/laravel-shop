<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaTopbarGeneralSettingsPlacementTest extends TestCase
{
    public function test_pharma_topbar_is_in_general_tab_not_builder_tab(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $generalStart = strpos($view, '<div x-show="tab === \'general\'"');
        $menuStart = strpos($view, '<div x-show="tab === \'menu\'"');
        $builderStart = strpos($view, '<div x-show="tab === \'builder\'"');
        $topbarStart = strpos($view, 'id="header-topbar-settings"');

        $this->assertNotFalse($generalStart);
        $this->assertNotFalse($menuStart);
        $this->assertNotFalse($builderStart);
        $this->assertNotFalse($topbarStart);
        $this->assertGreaterThan($generalStart, $topbarStart);
        $this->assertLessThan($menuStart, $topbarStart);
        $this->assertSame(1, substr_count($view, 'id="header-topbar-settings"'));
        $this->assertStringContainsString('wire:click="saveTopbarSettings"', $view);
    }
}

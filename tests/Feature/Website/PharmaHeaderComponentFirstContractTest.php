<?php

namespace Tests\Feature\Website;

use Modules\Website\Livewire\Admin\Header\HeaderSettingsHub;
use ReflectionMethod;
use Tests\TestCase;

final class PharmaHeaderComponentFirstContractTest extends TestCase
{
    public function test_legacy_duplicate_search_is_merged_without_losing_mobile_visibility(): void
    {
        $hub = new HeaderSettingsHub();
        $hub->editingTemplate = 'pharma';
        $hub->builderSlots = [
            'desktop.topbar' => [],
            'desktop.main.left' => [],
            'desktop.main.center' => [],
            'desktop.main.right' => [['type' => 'search', 'enabled' => false, 'config' => ['visibility' => 'hidden']]],
            'mobile.search' => [['type' => 'search', 'enabled' => true, 'config' => ['visibility' => 'mobile']]],
            'mobile.drawer' => [['type' => 'mobile-menu', 'enabled' => true, 'config' => ['visibility' => 'both']]],
        ];

        $method = new ReflectionMethod(HeaderSettingsHub::class, 'normalizePharmaBuilder');
        $method->invoke($hub);

        $this->assertCount(1, $hub->builderSlots['desktop.main.right']);
        $this->assertSame([], $hub->builderSlots['mobile.search']);
        $this->assertSame('mobile', $hub->builderSlots['desktop.main.right'][0]['config']['visibility']);
        $this->assertTrue($hub->builderSlots['desktop.main.right'][0]['enabled']);
        $this->assertSame('mobile', $hub->builderSlots['mobile.drawer'][0]['config']['visibility']);
    }

    public function test_pharma_admin_uses_component_first_rows_with_fixed_drawer_device(): void
    {
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/builder-preview.blade.php'));

        $this->assertStringContainsString('Danh sách thành phần Header Pharma', $admin);
        $this->assertStringContainsString('setPharmaComponentPosition', $admin);
        $this->assertStringContainsString('savePharmaBuilder(pharmaVisibilityOverrides)', $admin);
        $pharmaStart = strpos($admin, 'aria-label="Danh sách thành phần Header Pharma"');
        $legacyStart = strpos($admin, '<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">', $pharmaStart);
        $this->assertNotFalse($pharmaStart);
        $this->assertNotFalse($legacyStart);
        $pharmaMarkup = substr($admin, $pharmaStart, $legacyStart - $pharmaStart);
        $this->assertStringNotContainsString('wire:change="setComponentVisibility', $pharmaMarkup);
        $this->assertStringContainsString('@if(!$isDrawer)', $admin);
        $this->assertStringContainsString('mobileComponents', $preview);
        $this->assertStringContainsString('previewTopbarVisibility', $preview);
    }
}

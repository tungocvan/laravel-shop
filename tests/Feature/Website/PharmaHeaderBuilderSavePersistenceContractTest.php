<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaHeaderBuilderSavePersistenceContractTest extends TestCase
{
    public function test_pharma_visibility_is_submitted_explicitly_on_save_without_immediate_livewire_request(): void
    {
        $blade = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $start = strpos($blade, 'aria-label="Danh sách thành phần Header Pharma"');
        $end = strpos($blade, '<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $pharma = substr($blade, $start, $end - $start);
        $this->assertStringContainsString('pharmaVisibilityOverrides', $blade);
        $this->assertStringContainsString('savePharmaBuilder(pharmaVisibilityOverrides)', $blade);
        $this->assertStringContainsString('x-on:change="pharmaVisibilityOverrides', $pharma);
        $this->assertStringNotContainsString("$".'wire.set(\'builderSlots\', slots, false)', $pharma);
        $this->assertStringNotContainsString('wire:change="setComponentVisibility', $pharma);
    }

    public function test_server_applies_device_choices_before_persisting_builder(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));

        $start = strpos($component, 'public function savePharmaBuilder(');
        $end = strpos($component, 'public function saveBuilder(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $save = substr($component, $start, $end - $start);

        $this->assertStringContainsString('array $visibilityOverrides', $save);
        $this->assertStringContainsString("['both', 'desktop', 'mobile', 'hidden']", $save);
        $this->assertStringContainsString('if ($type === \'mobile-menu\')', $save);
        $this->assertStringContainsString("$".'settingsService->updateMany(', $save);
        $this->assertStringContainsString("$".'settingsService->get($key)', $save);
        $this->assertStringContainsString("$".'this->loadBuilderLayout($stored)', $save);
        $this->assertStringNotContainsString("$".'this->saveBuilder(', $save);
    }
}

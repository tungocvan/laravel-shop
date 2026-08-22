<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageBuilderStateConfigurationTest extends TestCase
{
    public function test_builder_mutations_are_preview_first_and_do_not_persist_before_save(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));

        $this->assertStringContainsString('public function duplicateSection(string $layoutKey, HomepageSectionRegistry $registry)', $component);
        $this->assertStringContainsString('public function removeSection(string $layoutKey): void', $component);
        $this->assertStringContainsString('public function restoreSection(string $layoutKey): void', $component);
        $this->assertStringContainsString('public function reorderSections(array $orderedKeys): void', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.home.manage')", $component);

        $this->assertStringNotContainsString('HomepageSectionManagerService', $component);
        $this->assertStringNotContainsString('$manager->duplicate(', $component);
        $this->assertStringNotContainsString('$manager->remove(', $component);
        $this->assertStringNotContainsString('$manager->restore(', $component);
        $this->assertStringContainsString('Bấm Lưu thay đổi để publish.', $component);
    }

    public function test_save_persists_builder_state_inside_the_homepage_write_transaction(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $writer = file_get_contents(base_path('Modules/Website/Services/HomepageContentWriteService.php'));
        $persistence = file_get_contents(base_path('Modules/Website/Services/HomepageBuilderPersistenceService.php'));

        $this->assertStringContainsString(
            '$writer->save($values, $this->sectionOrder, $this->layout, $this->sectionTypes)',
            $component
        );
        $this->assertStringContainsString('DB::transaction', $writer);
        $this->assertStringContainsString('$this->builderPersistence->sync($sectionOrder, $layout, $sectionTypes)', $writer);

        $this->assertStringContainsString("where('key', 'regexp', '_copy_[0-9]+$')", $persistence);
        $this->assertStringContainsString("'duplicated_from' => \$canonical", $persistence);
        $this->assertStringContainsString("'position' => (\$index + 1) * 10", $persistence);
        $this->assertStringContainsString("'is_enabled' => ! in_array(\$visibility, ['none', 'hidden'], true)", $persistence);
        $this->assertStringContainsString("'visibility' => \$visibility", $persistence);
    }

    public function test_drag_reorder_accepts_only_existing_builder_keys(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php'));

        $this->assertStringContainsString('in_array($key, $allowed, true)', $component);
        $this->assertStringContainsString('->unique()', $component);
        $this->assertStringContainsString('$wire.reorderSections(this.toArray())', $view);
        $this->assertStringContainsString("handle: '.section-drag-handle'", $view);
    }
}

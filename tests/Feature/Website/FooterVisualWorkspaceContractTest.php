<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterVisualWorkspaceContractTest extends TestCase
{
    public function test_visual_workspace_places_preview_first_and_keeps_theme_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        $this->assertStringContainsString('xl:order-1', $view);
        $this->assertStringContainsString('xl:order-2', $view);
        $this->assertStringContainsString('builder-preview', $view);
        $this->assertStringContainsString('theme-manager', $view);
        $this->assertStringContainsString('Bố cục & Nội dung', $view);
    }

    public function test_responsive_preview_shows_tablet_and_draft_column_items(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString("previewDevice = 'tablet'", $preview);
        $this->assertStringContainsString("max-w-[768px]", $preview);
        $this->assertStringContainsString("previewDevice !== 'mobile'", $preview);
        $this->assertStringContainsString("array_slice(\$column['items'] ?? [], 0, 8)", $preview);
        $this->assertStringContainsString('saveAndPublishTheme', $preview);
    }
    public function test_preview_selection_opens_matching_column_and_legacy_slot_editors(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString('selectFooterColumn(index)', $view);
        $this->assertStringContainsString('selectFooterSlot(slot)', $view);
        $this->assertStringContainsString('footer-column-editor-{{ $index }}', $view);
        $this->assertStringContainsString('footer-slot-{{ str_replace', $view);
        $this->assertStringContainsString('selectFooterColumn({{ $columnIndex }})', $preview);
        $this->assertStringContainsString('selectFooterSlot(@js($slotKey))', $preview);
        $this->assertStringContainsString('selectFooterSlot(\'mobile.main\')', $preview);
        $this->assertStringContainsString('@foreach($footerColumns as $columnIndex => $column)', $preview);
    }

    public function test_module_visibility_is_deferred_and_preview_uses_the_same_draft(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString("this.\$wire.set('builderSlots', this.builderSlotsDraft, false)", $view);
        $this->assertStringContainsString('toggleDraftComponent(@js($bottomSlot)', $view);
        $this->assertStringContainsString('toggleDraftComponent(@js($slot)', $view);
        $this->assertStringNotContainsString('wire:click="toggleComponent(', $view);
        $this->assertStringContainsString("isComponentVisible(@js(\$slotKey), {{ \$itemIndex }})", $preview);
        $this->assertStringContainsString("isComponentVisible('mobile.bottom', {{ \$itemIndex }})", $preview);
    }

}

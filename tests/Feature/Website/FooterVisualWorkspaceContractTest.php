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

        $this->assertStringNotContainsString("this.\$wire.set('builderSlots', this.builderSlotsDraft, false)", $view);
        $this->assertStringContainsString('toggleDraftComponent(@js($bottomSlot)', $view);
        $this->assertStringContainsString('toggleDraftComponent(@js($slot)', $view);
        $this->assertStringNotContainsString('wire:click="toggleComponent(', $view);
        $this->assertStringContainsString("isComponentVisible(@js(\$slotKey), {{ \$itemIndex }})", $preview);
        $this->assertStringContainsString("isComponentVisible('mobile.bottom', {{ \$itemIndex }})", $preview);
    }

    public function test_publish_controls_explain_when_theme_is_not_selected(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $manager = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/theme-manager.blade.php'));

        $this->assertStringContainsString("isset(\$layoutThemes[\$selectedTheme])", $preview);
        $this->assertStringContainsString('Chọn hoặc tạo theme để lưu', $preview);
        $this->assertStringContainsString("studioPanel = 'profile'", $preview);
        $this->assertStringContainsString('id="footer-theme-manager"', $manager);
        $this->assertStringContainsString('@disabled(!$selectedTheme)', $preview);
    }
    public function test_bottom_bar_preview_declares_item_index_used_by_visibility_binding(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString('x-for="item in (builderSlotsDraft[@js($slotKey)] || [])"', $preview);
        $this->assertStringNotContainsString('@forelse($slotItems[$slotKey] ?? [] as $item)', $preview);
    }
    public function test_save_and_publish_send_current_preview_draft_in_one_request(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));

        $this->assertStringContainsString('updateThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('function updateThemeFromDraft(', $component);
        $this->assertStringContainsString('function saveAndPublishThemeFromDraft(', $component);
        $this->assertStringContainsString('loadBuilderLayout($this->safeLayout($draft, $registry))', $component);
    }
    public function test_bottom_bar_reorder_updates_local_draft_and_preview_without_livewire_move(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));

        $this->assertStringContainsString('moveDraftComponent(slot, index, delta)', $view);
        $this->assertStringContainsString('this.builderSlotsDraft = { ...this.builderSlotsDraft, [slot]: reordered }', $view);
        $this->assertStringContainsString('moveDraftComponent(@js($bottomSlot), index, -1)', $view);
        $this->assertStringContainsString('moveDraftComponent(@js($bottomSlot), index, 1)', $view);
        $this->assertStringContainsString('builderSlotsDraft[@js($slotKey)]', $preview);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
    }
    public function test_draft_is_serialized_before_livewire_save(): void
    {
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));

        $this->assertStringContainsString('updateThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('saveAndPublishThemeFromDraft(JSON.stringify(builderSlotsDraft))', $preview);
        $this->assertStringContainsString('json_decode($draftJson, true, 512, JSON_THROW_ON_ERROR)', $component);
    }
    public function test_bottom_bar_has_no_duplicate_livewire_reorder_editor(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-settings-hub.blade.php'));

        $this->assertStringContainsString("@if(in_array(\$slot, ['desktop.bottom.left', 'desktop.bottom.right'], true))", $view);
        $this->assertStringContainsString('x-for="(item, index) in (builderSlotsDraft[@js($slot)] || [])"', $view);
        $this->assertStringContainsString('moveDraftComponent(@js($slot), index, -1)', $view);
        $this->assertStringContainsString('wire:ignore', $view);
        $this->assertStringContainsString('moveDraftComponent(@js($bottomSlot), index, -1)', $view);
    }
    public function test_frontend_footer_polish_keeps_published_profile_and_slots(): void
    {
        $shared = file_get_contents(base_path('Modules/Website/resources/views/partials/footer-shared.blade.php'));
        $columns = file_get_contents(base_path('Modules/Website/resources/views/components/footer/profile-column.blade.php'));
        $legal = file_get_contents(base_path('Modules/Website/resources/views/components/footer/legal-links.blade.php'));
        $badges = file_get_contents(base_path('Modules/Website/resources/views/components/footer/trust-badges.blade.php'));

        $this->assertStringContainsString('footerProfileColumnsActive', $shared);
        $this->assertStringContainsString("'desktop.bottom.left'", $shared);
        $this->assertStringContainsString("'desktop.bottom.right'", $shared);
        $this->assertStringContainsString('margin-bottom: clamp(24px, 3vw, 44px)', $shared);
        $this->assertStringContainsString('space-y-2.5 text-sm leading-6', $columns);
        $this->assertStringContainsString('gap-x-3 gap-y-1.5', $legal);
        $this->assertStringContainsString('max-w-24 object-contain', $badges);
    }
}

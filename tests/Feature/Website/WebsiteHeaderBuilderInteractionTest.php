<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHeaderBuilderInteractionTest extends TestCase
{
    public function test_drag_drop_is_validated_by_the_existing_registry_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));

        $this->assertStringContainsString('public function moveComponentByDrag(', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.settings.manage')", $component);
        $this->assertStringContainsString('$registry->resolve', $component);
        $this->assertStringContainsString('in_array($fromSlot, self::BUILDER_SLOTS', $component);
        $this->assertStringContainsString('in_array($toSlot, self::BUILDER_SLOTS', $component);
        $this->assertStringContainsString('array_splice($this->builderSlots[$toSlot]', $component);
    }

    public function test_builder_uses_native_drag_drop_without_external_sorting_dependency(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertStringContainsString('draggable="true"', $view);
        $this->assertStringContainsString('@dragstart=', $view);
        $this->assertStringContainsString('@dragover.prevent=', $view);
        $this->assertStringContainsString('@drop.prevent=', $view);
        $this->assertStringContainsString('moveComponentByDrag', $view);
        $this->assertStringNotContainsString('Sortable', $view);
        $this->assertStringNotContainsString('sortablejs', strtolower($view));
        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/sortable', strtolower($view));
    }

    public function test_responsive_preview_is_driven_by_unsaved_builder_state(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/builder-preview.blade.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));

        $this->assertStringContainsString("previewDevice: 'desktop'", $view);
        $this->assertStringContainsString("previewDevice = 'desktop'", $preview);
        $this->assertStringContainsString("previewDevice = 'tablet'", $preview);
        $this->assertStringContainsString("previewDevice = 'mobile'", $preview);
        $this->assertStringContainsString('$builderSlots', $preview);
        $this->assertStringContainsString('$previewPresentation', $preview);
        $this->assertStringContainsString("'previewPresentation' => \$presentationService->resolve(\$this->presentation)", $component);
        $this->assertStringNotContainsString('<iframe', strtolower($preview));
    }

    public function test_presentation_controls_use_live_updates_for_preview(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertStringContainsString('wire:model.live="presentation.container"', $view);
        $this->assertStringContainsString('wire:model.live="presentation.size"', $view);
        $this->assertStringContainsString('wire:model.live="presentation.inherit_colors"', $view);
        $this->assertStringContainsString('wire:model.live="presentation.colors.', $view);
        $this->assertStringContainsString('wire:model.live.debounce.250ms="presentation.custom.', $view);
    }
}

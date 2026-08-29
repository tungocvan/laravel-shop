<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaMedicineWorkspaceTest extends TestCase
{
    public function test_medicine_workspace_uses_bounded_page_sizes_without_all_mode(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringNotContainsString("'All'", $component);
        $this->assertStringNotContainsString('999999', $component);
        $this->assertStringNotContainsString('Hiển thị tất cả', $view);
        $this->assertStringContainsString('@foreach ($perPageOptions as $option)', $view);
    }

    public function test_selection_is_page_scoped_and_resets_when_workspace_context_changes(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));

        $this->assertStringContainsString('public bool $selectPage = false;', $component);
        $this->assertStringContainsString('$this->selectedIds = $value ? $this->currentPageIds() : [];', $component);
        $this->assertStringContainsString('array_intersect(array_map(\'strval\', $this->selectedIds), $pageIds)', $component);
        $this->assertStringContainsString('$this->resetWorkspacePage();', $component);
        $this->assertStringContainsString('$this->clearSelection();', $component);
    }

    public function test_destructive_controls_are_permission_aware_and_confirmed(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("$canDelete = $admin?->can('delete_pharma') ?? false;", $view);
        $this->assertStringContainsString('@if ($canDelete)', $view);
        $this->assertStringContainsString('wire:confirm="Xóa vĩnh viễn các hồ sơ thuốc đã chọn trên trang hiện tại?"', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
    }

    public function test_workspace_preserves_existing_named_routes_and_admin_navigation(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("route('admin.pharma.hssp.create')", $view);
        $this->assertStringContainsString("route('admin.pharma.hssp.edit', $medicine->id)", $view);
        $this->assertStringContainsString('MedicineImportExport::class', $view);
    }
}

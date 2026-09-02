<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class MenuLivewireRefactorContractTest extends TestCase
{
    public function test_menu_table_uses_checkbox_aware_export_and_modal_delete(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));
        $selectionBranch = '$this->selectedMenus === []';

        $this->assertStringContainsString($selectionBranch, $component);
        $this->assertStringContainsString('exportSelected($this->selectedMenus)', $component);
        $this->assertStringContainsString('public function requestBulkDelete(): void', $component);
        $this->assertStringContainsString('public bool $showBulkDeleteModal = false;', $component);
        $this->assertStringContainsString('wire:click="requestBulkDelete"', $view);
        $this->assertStringContainsString('@if ($showBulkDeleteModal)', $view);
        $this->assertStringNotContainsString('wire:click="exportSelected"', $view);
        $this->assertStringNotContainsString('wire:confirm="Xóa {{ count($selectedMenus) }} menu đã chọn?"', $view);
    }

    public function test_menu_table_reuses_shared_form_controls_and_full_admin_workspace_width(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString('<x-admin::form.input', $view);
        $this->assertStringContainsString('<x-admin::form.select', $view);
        $this->assertStringContainsString('<div class="px-4 sm:px-6 md:px-8"', $view);
    }

    public function test_menu_form_delegates_persistence_to_service_and_uses_shared_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuForm.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuService.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-form.blade.php'));

        $this->assertStringContainsString('$this->menuService->saveForm($validated, $this->menuId)', $component);
        $this->assertStringContainsString('public function saveForm(array $data, int|string|null $menuId = null): AdminMenu', $service);
        $this->assertStringContainsString('public function parentOptions(', $service);
        $this->assertStringContainsString('public function findForForm(', $service);
        $this->assertStringNotContainsString('AdminMenu::updateOrCreate(', $component);
        $this->assertStringNotContainsString('Lỗi khi lưu menu: ', $component);
        $this->assertStringContainsString('<x-admin::form.input', $view);
        $this->assertStringContainsString('<x-admin::form.select', $view);
        $this->assertStringNotContainsString('border-0', $view);
    }

    public function test_parent_selection_cascades_and_route_scanner_is_service_backed(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuService.php'));
        $item = file_get_contents(base_path('Modules/Admin/resources/views/components/menu-item.blade.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString('public function toggleMenuSelection(', $component);
        $this->assertStringContainsString('idsForBranch($menuId)', $component);
        $this->assertStringContainsString('public function idsForBranch(', $service);
        $this->assertStringContainsString('wire:click="toggleMenuSelection(', $item);
        $this->assertStringContainsString('openRouteScannerModal', $component);
        $this->assertStringContainsString('MenuRouteScannerService', $component);
        $this->assertStringContainsString('Quét GET routes chưa có trong Menu', $view);
        $this->assertStringContainsString('@if ($showRouteScannerModal)', $view);
    }

    public function test_full_export_uses_private_storage_snapshot_and_selected_export_does_not_refresh_it(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuImportExportService.php'));

        $this->assertStringContainsString("storage_path('app/menu/menus.json')", $service);
        $this->assertStringContainsString('$this->refreshRestoreSnapshot();', $service);
        $this->assertStringContainsString('public function exportSelected(array $menuIds): string', $service);
        $this->assertSame(1, substr_count($service, '$this->refreshRestoreSnapshot();'));
        $this->assertStringNotContainsString("base_path('Modules/Admin/data/menus.json')", $service);
    }

    public function test_route_scanner_only_targets_named_get_admin_routes_without_required_parameters(): void
    {
        $scanner = file_get_contents(base_path('Modules/Admin/Services/MenuRouteScannerService.php'));

        $this->assertStringContainsString("in_array('GET', \$methods, true)", $scanner);
        $this->assertStringContainsString("str_starts_with(\$name, 'admin.')", $scanner);
        $this->assertStringContainsString("str_starts_with(\$uri, 'admin/')", $scanner);
        $this->assertStringContainsString("str_contains(\$uri, '{')", $scanner);
        $this->assertStringContainsString("str_starts_with(\$middleware, 'permission:')", $scanner);
    }

    public function test_scanner_display_name_is_editable_module_filterable_and_submenus_start_collapsed(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $scanner = file_get_contents(base_path('Modules/Admin/Services/MenuRouteScannerService.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));
        $item = file_get_contents(base_path('Modules/Admin/resources/views/components/menu-item.blade.php'));

        $this->assertStringContainsString('public array $routeCandidateNames = [];', $component);
        $this->assertStringContainsString('persistSelected($this->selectedRouteCandidates, $this->routeCandidateNames)', $component);
        $this->assertStringContainsString('public function persistSelected(array $candidateIds, array $displayNames = []): int', $scanner);
        $this->assertStringContainsString('wire:model="routeCandidateNames.', $view);
        $this->assertStringContainsString('Tên hiển thị gợi ý', $view);
        $this->assertStringContainsString('id="route-module-filter"', $view);
        $this->assertStringContainsString('x-model="moduleFilter"', $view);
        $this->assertStringContainsString('Tất cả Module', $view);
        $this->assertStringContainsString("moduleFilter === 'all'", $view);
        $this->assertStringContainsString('expanded: false', $item);
        $this->assertStringContainsString('actionsOpen: false', $item);
    }

    public function test_scanned_menu_creation_reuses_soft_deleted_slugs_and_handles_failures_in_livewire(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuService.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));

        $this->assertStringContainsString('AdminMenu::withTrashed()', $service);
        $this->assertStringContainsString("->where('slug', \$parentSlug)->first()", $service);
        $this->assertStringContainsString('if ($parent->trashed())', $service);
        $this->assertStringContainsString('$parent->restore();', $service);
        $this->assertStringContainsString('$existing->restore();', $service);
        $this->assertStringContainsString('try {', $component);
        $this->assertStringContainsString('Khong the them route vao Menu.', $component);
    }
}

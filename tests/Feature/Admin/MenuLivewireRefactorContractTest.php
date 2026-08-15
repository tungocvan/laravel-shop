<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class MenuLivewireRefactorContractTest extends TestCase
{
    public function test_menu_table_uses_checkbox_aware_export_and_modal_delete(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString("$this->selectedMenus === []", $component);
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
        $this->assertStringNotContainsString('max-w-5xl', $view);
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
}

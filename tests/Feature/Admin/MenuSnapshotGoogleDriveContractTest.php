<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class MenuSnapshotGoogleDriveContractTest extends TestCase
{
    public function test_admin_snapshot_library_uses_system_owned_google_drive_boundary(): void
    {
        $adminService = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));
        $systemService = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDrivePortableFileService.php'));

        $this->assertStringContainsString('GoogleDrivePortableFileService', $adminService);
        $this->assertStringContainsString("public const CLOUD_DIRECTORY = 'Admin/Menu';", $adminService);
        $this->assertStringContainsString("storage_path('app/menu/menus.json')", $adminService);
        $this->assertStringContainsString("return 'menus-'.\$slug.'.json';", $adminService);
        $this->assertStringContainsString('public function list(string $relativeDirectory): array', $systemService);
        $this->assertStringContainsString('public function rename(string $relativePath, string $newFileName): array', $systemService);
        $this->assertStringContainsString('public function delete(string $relativePath): void', $systemService);
        $this->assertStringContainsString('GoogleDriveConnectionService', $systemService);
    }

    public function test_snapshot_modal_uses_explicit_selected_or_all_scope_for_excel_and_drive_json(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString("public string \$snapshotExportScope = 'all';", $component);
        $this->assertStringContainsString("\$this->snapshotExportScope = \$this->selectedMenus === [] ? 'all' : 'selected';", $component);
        $this->assertStringContainsString("\$isSelected = \$this->snapshotExportScope === 'selected';", $component);
        $this->assertStringContainsString('exportSelected($this->selectedMenus)', $component);
        $this->assertStringContainsString('pushSelectedSnapshotBestEffort($this->snapshotName, $this->selectedMenus)', $component);
        $this->assertStringContainsString('pushLocalSnapshotBestEffort($this->snapshotName)', $component);
        $this->assertStringContainsString('public function pushSelectedSnapshot(string $snapshotName, array $menuIds): array', $service);
        $this->assertStringContainsString('private function selectedSnapshotContent(array $menuIds): string', $service);
        $this->assertStringContainsString('wire:model.live="snapshotExportScope" value="selected"', $view);
        $this->assertStringContainsString('Các menu đang chọn ({{ count($selectedMenus) }})', $view);
        $this->assertStringContainsString('wire:model.live="snapshotExportScope" value="all"', $view);
        $this->assertStringContainsString('Toàn bộ menu', $view);
    }

    public function test_cloud_pull_accepts_selected_snapshot_and_validates_before_atomic_local_replacement(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));

        $this->assertStringContainsString('public function pullToLocal(string $snapshotFile): array', $service);
        $this->assertStringContainsString("self::CLOUD_DIRECTORY.'/'.\$snapshotFile", $service);

        $validationOffset = strpos($service, '$this->assertValidSnapshot($content);', strpos($service, 'public function pullToLocal'));
        $temporaryOffset = strpos($service, '$temporary = $path.\'.sync.tmp\';', strpos($service, 'public function pullToLocal'));

        $this->assertNotFalse($validationOffset);
        $this->assertNotFalse($temporaryOffset);
        $this->assertLessThan($temporaryOffset, $validationOffset);
        $this->assertStringContainsString('File::move($temporary, $path)', $service);
    }

    public function test_snapshot_listing_and_management_are_scoped_to_menu_json_files(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString('$this->cloudFiles->list(self::CLOUD_DIRECTORY)', $service);
        $this->assertStringContainsString('isAllowedSnapshotFile', $service);
        $this->assertStringContainsString("preg_match('/\\Amenus(?:-[a-z0-9][a-z0-9-]{0,80})?\\.json\\z/'", $service);
        $this->assertStringContainsString('public function renameSnapshot(string $snapshotFile, string $newSnapshotName): array', $service);
        $this->assertStringContainsString('public function deleteSnapshots(array $snapshotFiles): int', $service);
        $this->assertStringContainsString('public array $selectedCloudSnapshotFiles = [];', $component);
        $this->assertStringContainsString('public function saveRenameSnapshot(): void', $component);
        $this->assertStringContainsString('public function deleteSelectedSnapshots(): void', $component);
        $this->assertStringContainsString('wire:model.live="selectedCloudSnapshotFiles"', $view);
        $this->assertStringContainsString('wire:click="saveRenameSnapshot"', $view);
        $this->assertStringContainsString('wire:click="deleteSelectedSnapshots"', $view);
        $this->assertStringContainsString('Đổi tên', $view);
    }

    public function test_menu_ui_exposes_export_modal_drive_library_and_specific_snapshot_sync(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString('public bool $showSnapshotModal = false;', $component);
        $this->assertStringContainsString('public array $cloudSnapshots = [];', $component);
        $this->assertStringContainsString('public function syncSnapshotFromGoogleDrive(string $snapshotFile): void', $component);
        $this->assertStringContainsString("authorizePermission('admin.menu.restore')", $component);
        $this->assertStringContainsString('Export & thư viện snapshot Menu', $view);
        $this->assertStringContainsString('wire:click="exportWithSnapshot"', $view);
        $this->assertStringContainsString('Quản lý snapshot Google Drive', $view);
        $this->assertStringContainsString('Đồng bộ về local', $view);
        $this->assertStringContainsString('storage/app/menu/menus.json', $view);
        $this->assertStringContainsString('Khôi phục snapshot', $view);
    }
}

<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class MenuSnapshotGoogleDriveContractTest extends TestCase
{
    public function test_admin_snapshot_sync_uses_system_owned_google_drive_boundary(): void
    {
        $adminService = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));
        $systemService = file_get_contents(base_path('Modules/System/Services/Cloud/GoogleDrivePortableFileService.php'));

        $this->assertStringContainsString('GoogleDrivePortableFileService', $adminService);
        $this->assertStringContainsString("public const CLOUD_PATH = 'Admin/Menu/menus.json';", $adminService);
        $this->assertStringContainsString("storage_path('app/menu/menus.json')", $adminService);
        $this->assertStringContainsString('GoogleDriveConnectionService', $systemService);
        $this->assertStringContainsString("'https://www.googleapis.com/upload/drive/v3/files/'", $systemService);
    }

    public function test_full_export_pushes_snapshot_best_effort_but_selected_export_does_not(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));

        $this->assertStringContainsString('$isFullExport = $this->selectedMenus === [];', $component);
        $this->assertStringContainsString('pushLocalSnapshotBestEffort()', $component);
        $this->assertStringContainsString('exportSelected($this->selectedMenus)', $component);
        $this->assertStringContainsString('Export thanh cong, nhung snapshot Google Drive chua dong bo duoc.', $component);
    }

    public function test_cloud_pull_validates_before_atomic_local_replacement(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/MenuSnapshotCloudSyncService.php'));

        $validationOffset = strpos($service, '$this->assertValidSnapshot($content);');
        $temporaryOffset = strpos($service, '$temporary = $path.\'.sync.tmp\';');

        $this->assertNotFalse($validationOffset);
        $this->assertNotFalse($temporaryOffset);
        $this->assertLessThan($temporaryOffset, $validationOffset);
        $this->assertStringContainsString('File::move($temporary, $path)', $service);
    }

    public function test_menu_ui_exposes_drive_status_sync_and_restore_as_secondary_tools(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Menus/MenuTable.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/menus/menu-table.blade.php'));

        $this->assertStringContainsString('public function syncSnapshotFromGoogleDrive(): void', $component);
        $this->assertStringContainsString("authorizePermission('admin.menu.restore')", $component);
        $this->assertStringContainsString("'snapshotStatus' => \$this->snapshotCloudSyncService->status()", $component);
        $this->assertStringContainsString('wire:click="syncSnapshotFromGoogleDrive"', $view);
        $this->assertStringContainsString('Laravel-Backup/Admin/Menu/menus.json', $view);
        $this->assertStringContainsString('Khôi phục snapshot', $view);
    }
}

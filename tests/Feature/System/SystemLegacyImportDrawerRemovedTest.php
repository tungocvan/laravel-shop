<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemLegacyImportDrawerRemovedTest extends TestCase
{
    public function test_legacy_import_drawer_is_removed_and_canonical_import_apis_remain(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/System/Livewire/Database/ImportDrawer.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/database/import-drawer.blade.php'));

        $service = file_get_contents(base_path('Modules/System/Services/DatabaseService.php'));
        $this->assertStringContainsString('function importBackupFile', $service);
        $this->assertStringContainsString('function importTableFromFile', $service);
        $this->assertStringContainsString('function restoreFromFile', $service);
        $this->assertStringNotContainsString('function import(', $service);
    }
}

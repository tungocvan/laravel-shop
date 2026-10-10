<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class ModuleLocalSnapshotTransferContractTest extends TestCase
{
    public function test_local_snapshot_transfer_uses_existing_validated_service_and_permissions(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Livewire/Database/TableList.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list.blade.php'));

        $this->assertStringContainsString("authorizePermission('database.download')", $controller);
        $this->assertStringContainsString('resolveLocalReference($reference, $this->moduleFilter)', $controller);
        $this->assertStringContainsString("validatePackage(\$snapshot['absolute_path'], \$this->moduleFilter, enforceSchema: false)", $controller);
        $this->assertStringContainsString("authorizePermission('database.backup')", $controller);
        $this->assertStringContainsString('importDownloadedPackage(', $controller);
        $this->assertStringContainsString('downloadLocalModuleSnapshot(', $view);
        $this->assertStringContainsString('wire:submit="importLocalModuleSnapshot"', $view);
        $this->assertStringContainsString('wire:model="moduleSnapshotUpload"', $view);
    }
}

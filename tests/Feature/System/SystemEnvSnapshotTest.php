<?php

namespace Tests\Feature\System;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemEnvSnapshotTest extends TestCase
{
    #[Test]
    public function env_manager_enforces_update_permission_and_has_no_dead_tab_state(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/EnvManager.php'));

        $this->assertStringContainsString('use AuthorizesSystemActions;', $source);
        $this->assertStringContainsString("authorizePermission('system.env.update')", $source);
        $this->assertStringContainsString('createSnapshot(string $operation', $source);
        $this->assertStringNotContainsString('getTabsDefinition', $source);
        $this->assertStringNotContainsString('activeTab', $source);
        $this->assertStringNotContainsString('exportEnv(', $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
    }

    #[Test]
    public function snapshot_service_owns_fixed_operations_private_storage_lock_and_retention(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Env/EnvSnapshotService.php'));

        $this->assertStringContainsString("'production' => 'Production'", $source);
        $this->assertStringContainsString("'local' => 'Local'", $source);
        $this->assertStringContainsString("storage_path('app/private/backups/env-snapshots')", $source);
        $this->assertStringContainsString("Cache::lock('system:env-snapshot:create'", $source);
        $this->assertStringContainsString('RETENTION_PER_TYPE = 5', $source);
        $this->assertStringContainsString('@chmod($directory, 0700)', $source);
        $this->assertStringContainsString('@chmod($path, 0600)', $source);
        $this->assertStringContainsString("'env-' . \$operation . '-*.env'", $source);
        $this->assertStringNotContainsString('base_path(".env.{$operation}")', $source);
    }

    #[Test]
    public function env_page_mounts_snapshot_toolbar_and_ui_has_no_free_form_path_input(): void
    {
        $page = file_get_contents(base_path('Modules/System/resources/views/pages/settings/env.blade.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/env-manager.blade.php'));

        $this->assertStringContainsString("@livewire('system.settings.env-manager')", $page);
        $this->assertStringContainsString("createSnapshot('production')", $view);
        $this->assertStringContainsString("createSnapshot('local')", $view);
        $this->assertStringContainsString('wire:confirm=', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
        $this->assertStringNotContainsString('wire:model', $view);
        $this->assertStringNotContainsString('<input', $view);
    }

    #[Test]
    public function legacy_project_root_export_is_not_used_by_livewire(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/EnvManager.php'));

        $this->assertStringNotContainsString('EnvManagerService', $component);
        $this->assertStringNotContainsString('exportToEnvironment', $component);
        $this->assertStringContainsString('EnvSnapshotService', $component);
    }
}

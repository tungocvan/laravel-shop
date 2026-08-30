<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminDatabaseIsolationContractTest extends TestCase
{
    public function test_active_admin_routes_do_not_expose_database_administration(): void
    {
        $webRoutes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $apiRoutes = file_get_contents(base_path('Modules/Admin/routes/api.php'));

        $this->assertNotFalse($webRoutes);
        $this->assertNotFalse($apiRoutes);

        foreach (['DatabaseController', 'DatabaseService', "prefix('database')", "'/database'", '"/database"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $webRoutes);
            $this->assertStringNotContainsString($forbidden, $apiRoutes);
        }

        $this->assertStringNotContainsString('Route::', $apiRoutes);
    }

    public function test_legacy_database_livewire_component_remains_fail_closed(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Database/TableList.php'));

        $this->assertNotFalse($component);
        $this->assertStringContainsString('public bool $databaseActionsDisabled = true;', $component);
        $this->assertStringContainsString("'tables' => []", $component);
        $this->assertStringContainsString("abort(403, 'Database administration is disabled until P0 controls are implemented.');", $component);

        foreach ([
            'backupFull',
            'exportTable',
            'restoreTable',
            'truncateTable',
            'dropTable',
            'openRestoreModal',
            'restoreDatabase',
        ] as $method) {
            $pattern = '/public function '.preg_quote($method, '/').'\([^)]*\): void\s*\{\s*\$this->denyDatabaseAction\(\);\s*\}/s';

            $this->assertMatchesRegularExpression(
                $pattern,
                $component,
                sprintf('Admin database action [%s] must remain fail closed.', $method)
            );
        }
    }

    public function test_database_livewire_component_does_not_reference_dangerous_service(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Database/TableList.php'));

        $this->assertNotFalse($component);
        $this->assertStringNotContainsString('DatabaseService', $component);
        $this->assertStringNotContainsString('Modules\\Admin\\Services\\DatabaseService', $component);
    }
}

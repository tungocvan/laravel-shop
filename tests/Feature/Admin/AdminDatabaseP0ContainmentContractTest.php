<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Livewire\Database\BackupManager;
use Modules\Admin\Livewire\Database\ImportDrawer;
use Modules\Admin\Livewire\Database\TableList;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminDatabaseP0ContainmentContractTest extends TestCase
{
    public function test_admin_routes_do_not_expose_database_administration(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $apiRoutes = file_get_contents(base_path('Modules/Admin/routes/api.php'));

        $this->assertStringNotContainsString('DatabaseService', $routes);
        $this->assertStringNotContainsString('Livewire\\Database', $routes);
        $this->assertStringNotContainsString('database', strtolower($routes));
        $this->assertStringNotContainsString('DatabaseService', $apiRoutes);
    }

    public function test_legacy_database_livewire_surfaces_do_not_resolve_database_service(): void
    {
        foreach ([
            'Modules/Admin/Livewire/Database/TableList.php',
            'Modules/Admin/Livewire/Database/BackupManager.php',
            'Modules/Admin/Livewire/Database/ImportDrawer.php',
        ] as $path) {
            $source = file_get_contents(base_path($path));

            $this->assertStringNotContainsString('use Modules\\Admin\\Services\\DatabaseService;', $source, $path);
            $this->assertStringNotContainsString('DatabaseService $service', $source, $path);
        }
    }

    public function test_legacy_database_surfaces_render_no_operational_data(): void
    {
        $tableList = file_get_contents(base_path('Modules/Admin/Livewire/Database/TableList.php'));
        $backupManager = file_get_contents(base_path('Modules/Admin/Livewire/Database/BackupManager.php'));

        $this->assertStringContainsString("'tables' => []", $tableList);
        $this->assertStringContainsString("'backups' => []", $backupManager);
    }

    public function test_destructive_livewire_actions_fail_closed_with_403(): void
    {
        $actions = [
            [new TableList(), 'backupFull', []],
            [new TableList(), 'exportTable', ['wp_products']],
            [new TableList(), 'restoreTable', ['wp_products']],
            [new TableList(), 'truncateTable', ['wp_products']],
            [new TableList(), 'dropTable', ['wp_products']],
            [new TableList(), 'openRestoreModal', []],
            [new TableList(), 'restoreDatabase', []],
            [new BackupManager(), 'restoreBackup', ['backup.sql']],
            [new BackupManager(), 'restore', ['backup.sql']],
            [new ImportDrawer(), 'save', []],
        ];

        foreach ($actions as [$component, $method, $arguments]) {
            try {
                $component->{$method}(...$arguments);
                $this->fail(sprintf('%s::%s did not fail closed.', $component::class, $method));
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode(), sprintf('%s::%s', $component::class, $method));
            }
        }
    }

    public function test_database_service_remains_quarantined_not_deleted_or_rehomed(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));
        $this->assertFileExists(base_path('Modules/System/Livewire/Settings/DatabaseConfig.php'));

        $service = file_get_contents(base_path('Modules/Admin/Services/DatabaseService.php'));
        $this->assertStringContainsString('class DatabaseService', $service);
        $this->assertStringContainsString('function dropTable', $service);
        $this->assertStringContainsString('function restoreFromFile', $service);
    }
}

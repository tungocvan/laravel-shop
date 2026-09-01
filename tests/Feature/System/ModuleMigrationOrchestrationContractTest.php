<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class ModuleMigrationOrchestrationContractTest extends TestCase
{
    public function test_default_module_migrate_uses_canonical_lifecycle_path(): void
    {
        $command = file_get_contents(base_path('app/Console/Commands/Module/MigrateCommand.php'));

        $this->assertIsString($command);
        $this->assertStringContainsString('migrateCanonical', $command);
        $this->assertStringNotContainsString('hasPendingMigrations($module)', $command);
    }

    public function test_canonical_migration_resolves_dependencies_and_uses_lifecycle_manager(): void
    {
        $migrator = file_get_contents(base_path('app/Modules/Migration/Services/ModuleMigrator.php'));

        $this->assertIsString($migrator);
        $this->assertStringContainsString('ModuleLifecycleManager', $migrator);
        $this->assertStringContainsString('$this->resolver->resolve($module)', $migrator);
        $this->assertStringContainsString('$this->lifecycle->migrateIfNeeded', $migrator);
        $this->assertStringContainsString("base_path(\"Modules/{\$module}\")", $migrator);
    }

    public function test_legacy_destructive_flows_remain_separate_from_safe_default_path(): void
    {
        $migrator = file_get_contents(base_path('app/Modules/Migration/Services/ModuleMigrator.php'));

        $this->assertIsString($migrator);
        $this->assertStringContainsString('public function migrateCanonical', $migrator);
        $this->assertStringContainsString('public function fresh', $migrator);
        $this->assertStringContainsString('public function refresh', $migrator);
        $this->assertStringContainsString('module_migrations', $migrator);
    }
}

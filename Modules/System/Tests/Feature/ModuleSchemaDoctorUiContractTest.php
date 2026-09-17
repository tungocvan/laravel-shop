<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Tests\TestCase;

class ModuleSchemaDoctorUiContractTest extends TestCase
{
    public function test_database_workspace_exposes_read_only_schema_doctor(): void
    {
        $wrapper = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list-with-dependencies.blade.php'));
        $panel = file_get_contents(base_path('Modules/System/resources/views/livewire/database/module-schema-doctor-panel.blade.php'));
        $component = file_get_contents(base_path('Modules/System/Livewire/Database/ModuleSchemaDoctorPanel.php'));

        self::assertIsString($wrapper);
        self::assertIsString($panel);
        self::assertIsString($component);
        self::assertStringContainsString('ModuleSchemaDoctorPanel::class', $wrapper);
        self::assertStringContainsString('Chẩn đoán Schema', $panel);
        self::assertStringContainsString('Fix Plan an toàn', $panel);
        self::assertStringContainsString('Auto-repair hiện bị khóa', $panel);
        self::assertStringContainsString('Snapshot', $panel);
        self::assertStringContainsString('Hiện tại', $panel);
        self::assertStringContainsString("\$issue['differences']", $panel);
        self::assertStringContainsString('ModuleSchemaDoctorService $doctor', $component);
        self::assertStringNotContainsString('DROP TABLE', $component);
        self::assertStringNotContainsString('ALTER TABLE', $component);
    }
}

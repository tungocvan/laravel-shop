<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleSnapshotRecoveryDiagnosisContractTest extends TestCase
{
    #[Test]
    public function blocked_snapshot_explains_schema_mismatch_and_recovery_action(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('Không thể Restore Module: Preflight phát hiện khác biệt schema không an toàn.', $view);
        $this->assertStringContainsString('Kiểm tra chi tiết compatibility report và migration trước khi phục hồi.', $view);
        $this->assertStringContainsString('Snapshot Module là package độc lập gồm manifest, checksum và SQL', $view);
        $this->assertStringContainsString('Snapshot v2 sẽ dùng schema production hiện tại', $view);
        $this->assertStringContainsString('Không nên ép bỏ qua validation', $view);
        $this->assertStringContainsString('Preflight phát hiện khác biệt schema không an toàn', $view);
    }

    #[Test]
    public function restore_remains_available_only_for_compatible_snapshots(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString("in_array(\$snapshot['compatibility'], ['COMPATIBLE', 'WARNING'], true)", $view);
        $this->assertStringContainsString('openModuleRestoreModal', $view);
        $this->assertStringContainsString('Tải từ Drive về Local không tự làm snapshot không tương thích', $view);
    }
}

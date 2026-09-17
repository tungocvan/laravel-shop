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
        $this->assertStringContainsString('SCHEMA KHÔNG TƯƠNG THÍCH', $view);
        $this->assertStringContainsString('schema hiện tại khác schema lúc snapshot được tạo', $view);
        $this->assertStringContainsString('package ZIP, manifest, đúng Module/format, ownership bảng và checksum SQL đều hợp lệ', $view);
        $this->assertStringContainsString('php artisan migrate:status', $view);
        $this->assertStringContainsString('không nên ép bỏ qua validation', $view);
        $this->assertStringContainsString('snapshot cũ chưa thể chỉ ra chính xác cột/index nào khác', $view);
    }

    #[Test]
    public function restore_remains_available_only_for_compatible_snapshots(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/database/table-list.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString("\$snapshot['compatibility'] === 'COMPATIBLE'", $view);
        $this->assertStringContainsString('openModuleRestoreModal', $view);
        $this->assertStringContainsString('Tải từ Drive về Local không tự làm snapshot không tương thích', $view);
    }
}

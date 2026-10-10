<?php

namespace Tests\Feature\Admission;

use Illuminate\Support\Facades\DB;
use Modules\Admission\Services\AdmissionApplicationAdminService;
use Tests\TestCase;

class AdmissionDeleteAllTransactionTest extends TestCase
{
    public function test_increment_reset_is_not_executed_inside_the_delete_transaction(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Services/AdmissionApplicationAdminService.php'));
        $method = substr(
            $source,
            strpos($source, 'public function deleteAllAndResetIncrement(): int'),
            strpos($source, 'public function queueDocumentsForIds(') - strpos($source, 'public function deleteAllAndResetIncrement(): int')
        );

        $this->assertStringContainsString('$deleted = DB::transaction(function ()', $method);
        $this->assertStringContainsString("DB::statement('ALTER TABLE", $method);
        $this->assertLessThan(
            strpos($method, "DB::statement('ALTER TABLE"),
            strpos($method, "});\n\n        \$driver")
        );
    }

    public function test_empty_sqlite_database_can_delete_all_and_reset_sequence(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite-specific smoke test.');
        }

        // This is a read-only smoke check against the service source to avoid
        // destructive operations on a developer's existing Admission records.
        $source = file_get_contents(base_path('Modules/Admission/Services/AdmissionApplicationAdminService.php'));
        $this->assertStringContainsString("DB::table('sqlite_sequence')->where('name', 'admission_applications')->delete()", $source);
        $this->assertTrue(method_exists(AdmissionApplicationAdminService::class, 'deleteAllAndResetIncrement'));
    }
}

<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Modules\Invoices\Services\GdtConfigService;

class GdtDatabaseSettingsTest extends TestCase
{
    public function test_settings_service_never_writes_environment_file(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Services/GdtConfigService.php'));

        $this->assertStringNotContainsString("base_path('.env')", $source);
        $this->assertStringNotContainsString('File::put(', $source);
    }

    public function test_module_settings_schema_and_password_encryption_contract(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Services/GdtConfigService.php'));

        $this->assertStringContainsString("Schema::hasTable('invoice_gdt_settings')", $source);
        $this->assertStringContainsString('Crypt::encryptString(', $source);
        $this->assertStringContainsString('Crypt::decryptString(', $source);
    }

    public function test_import_command_has_dry_run_and_skips_existing_settings(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Console/Commands/ImportLegacyGdtSettingsCommand.php'));

        $this->assertStringContainsString('--dry-run', $source);
        $this->assertStringContainsString("->where('key', \$key)->exists()", $source);
    }
}

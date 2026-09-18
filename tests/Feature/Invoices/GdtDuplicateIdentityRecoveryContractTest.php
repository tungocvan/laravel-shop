<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtDuplicateIdentityRecoveryContractTest extends TestCase
{
    #[Test]
    public function gdt_persistence_reconciles_canonical_raw_and_legacy_transaction_identity(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));

        $this->assertStringContainsString('findExistingInvoice($attributes, $raw)', $service);
        $this->assertStringContainsString('->where(\'header_hash\', $headerHash)', $service);
        $this->assertStringContainsString('extractTransactionId($raw)', $service);
        $this->assertStringContainsString("'TransactionID'", $service);
        $this->assertStringContainsString('->where(\'total_amount\', $attributes[\'total_amount\'])', $service);
        $this->assertStringContainsString('->where(\'vat_amount\', $attributes[\'vat_amount\'])', $service);
    }

    #[Test]
    public function duplicate_recovery_is_dry_run_by_default_and_guards_references(): void
    {
        $recovery = file_get_contents(base_path('Modules/Invoices/Services/GdtDuplicateRecoveryService.php'));
        $command = file_get_contents(base_path('Modules/Invoices/Console/Commands/RecoverGdtDuplicatesCommand.php'));

        $this->assertStringContainsString('bool $apply = false', $recovery);
        $this->assertStringContainsString('header_hash', $recovery);
        $this->assertStringContainsString('detail_hash', $recovery);
        $this->assertStringContainsString('invoice_files', $recovery);
        $this->assertStringContainsString('invoice_inventory_snapshots', $recovery);
        $this->assertStringContainsString('mergeSourceMetadata', $recovery);
        $this->assertStringContainsString('invoices:recover-gdt-duplicates', $command);
        $this->assertStringContainsString('{--year= : Giới hạn theo năm; để trống = toàn bộ dữ liệu}', $command);
        $this->assertStringContainsString('{--type=all : all, sold hoặc purchase}', $command);
        $this->assertStringContainsString('public function recover(?int $year = null, string $invoiceType = \'all\'', $recovery);
        $this->assertStringContainsString('if ($year !== null)', $recovery);
        $this->assertStringContainsString('if ($invoiceType !== \'all\')', $recovery);
        $this->assertStringContainsString('{--apply', $command);
    }

    #[Test]
    public function provider_registers_the_recovery_command(): void
    {
        $provider = file_get_contents(base_path('Modules/Invoices/Providers/InvoicesServiceProvider.php'));

        $this->assertStringContainsString('RecoverGdtDuplicatesCommand::class', $provider);
    }
}

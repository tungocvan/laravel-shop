<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Data\InvoiceDebtCandidate;
use Modules\Finance\Integrations\Invoices\DatabaseInvoiceDebtCandidateReader;
use Tests\TestCase;

final class InvoiceDebtCandidateReaderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('invoices')) {
            (require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php'))->up();
        }
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    public function test_candidate_identity_and_direction_are_explicit(): void
    {
        $candidate = new InvoiceDebtCandidate('15', 'HD0015', 'receivable', '150.00', '2026-10-24', 'Buyer', '123456789');
        self::assertSame('Invoices:invoice:15', $candidate->sourceKey());
        self::assertSame('receivable', $candidate->direction);
    }

    public function test_invoice_candidate_is_read_only_and_does_not_create_finance_debt(): void
    {
        config()->set('modules.registry.Invoices.enabled', true);
        $id = DB::table('invoices')->insertGetId([
            'invoice_number' => 'FIN-DEBT-'.uniqid(),
            'invoice_type' => 'sold',
            'issued_date' => '2026-10-24',
            'name' => 'Test customer',
            'tax_code' => '0123456789',
            'total_amount' => '123456.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $candidate = (new DatabaseInvoiceDebtCandidateReader)->find((string) $id);
        self::assertSame('receivable', $candidate->direction);
        self::assertSame('123456.00', $candidate->amount);
        self::assertSame((string) $id, $candidate->documentId);
        self::assertSame(0, DB::table('finance_debt_obligations')
            ->where('source_module', 'Invoices')
            ->where('source_document_id', (string) $id)->count());
    }

    public function test_purchase_invoice_maps_to_payable_and_disabled_module_fails_closed(): void
    {
        config()->set('modules.registry.Invoices.enabled', true);
        $id = DB::table('invoices')->insertGetId([
            'invoice_number' => 'FIN-PURCHASE-'.uniqid(),
            'invoice_type' => 'purchase',
            'issued_date' => '2026-10-24',
            'name' => 'Test supplier',
            'total_amount' => '200.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        self::assertSame('payable', (new DatabaseInvoiceDebtCandidateReader)->find((string) $id)->direction);

        config()->set('modules.registry.Invoices.enabled', false);
        $this->expectException(ValidationException::class);
        (new DatabaseInvoiceDebtCandidateReader)->find((string) $id);
    }
}

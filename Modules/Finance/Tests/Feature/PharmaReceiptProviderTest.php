<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Integrations\Pharma\PharmaReceiptProvider;
use Tests\TestCase;

final class PharmaReceiptProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pharma_inventory_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('number');
            $table->string('status');
            $table->string('supplier_name')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
        });
        Schema::create('pharma_inventory_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('invoice_unit_price_ex_vat', 18, 4)->nullable();
            $table->decimal('unit_price_ex_vat', 18, 4)->default(0);
            $table->decimal('vat_rate', 5, 2);
        });

        config(['modules.registry.Pharma.enabled' => true]);
    }

    public function test_posted_invoiced_receipt_resolves_payable_without_mutating_pharma(): void
    {
        $id = $this->receipt('posted', 'INV-01');
        DB::table('pharma_inventory_receipt_items')->insert([
            'receipt_id' => $id, 'quantity' => '2.000',
            'invoice_unit_price_ex_vat' => '100.0000', 'vat_rate' => '10.00',
        ]);

        $snapshot = (new PharmaReceiptProvider)->resolveForPosting((string) $id);

        $this->assertSame('Pharma:inventory_receipt:'.$id, $snapshot->key());
        $this->assertSame('payable', $snapshot->direction);
        $this->assertSame('VND', $snapshot->currency);
        $this->assertSame(220, (int) $snapshot->amount);
        $this->assertDatabaseHas('pharma_inventory_receipts', ['id' => $id, 'status' => 'posted']);
    }

    public function test_draft_receipt_is_not_financially_eligible(): void
    {
        $id = $this->receipt('draft', 'INV-02');
        $this->expectException(ValidationException::class);
        (new PharmaReceiptProvider)->resolveForPosting((string) $id);
    }

    public function test_posted_receipt_without_invoice_uses_base_price(): void
    {
        $id = $this->receipt('posted', null);
        DB::table('pharma_inventory_receipt_items')->insert([
            'receipt_id' => $id, 'quantity' => '2.000',
            'unit_price_ex_vat' => '100.0000', 'vat_rate' => '10.00',
        ]);
        $snapshot = (new PharmaReceiptProvider)->resolveForPosting((string) $id);
        $this->assertSame('payable', $snapshot->direction);
        $this->assertSame(220, (int) $snapshot->amount);
    }

    public function test_disabled_pharma_fails_closed(): void
    {
        config(['modules.registry.Pharma.enabled' => false]);
        $this->expectException(ValidationException::class);
        (new PharmaReceiptProvider)->resolveForPosting('1');
    }

    public function test_missing_invoice_price_is_rejected(): void
    {
        $id = $this->receipt('posted', 'INV-03');
        DB::table('pharma_inventory_receipt_items')->insert([
            'receipt_id' => $id, 'quantity' => '1.000',
            'invoice_unit_price_ex_vat' => null, 'unit_price_ex_vat' => '0.0000', 'vat_rate' => '10.00',
        ]);
        $this->expectException(ValidationException::class);
        (new PharmaReceiptProvider)->resolveForPosting((string) $id);
    }

    private function receipt(string $status, ?string $invoice): int
    {
        return DB::table('pharma_inventory_receipts')->insertGetId([
            'number' => 'PN-'.uniqid(), 'status' => $status,
            'supplier_name' => 'Supplier', 'invoice_number' => $invoice,
            'invoice_date' => '2026-10-21',
        ]);
    }
}

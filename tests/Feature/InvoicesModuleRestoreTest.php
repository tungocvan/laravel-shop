<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Invoices\Services\InvoiceModuleRestoreService;
use Modules\Invoices\Services\InvoiceModuleSnapshotService;
use RuntimeException;
use Tests\Concerns\CreatesInvoicesRestoreSchema;
use Tests\TestCase;

class InvoicesModuleRestoreTest extends TestCase
{
    use CreatesInvoicesRestoreSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createInvoicesRestoreSchema();
        Storage::fake('local');
    }

    public function test_merge_restore_preserves_newer_current_data_and_restores_missing_rows(): void
    {
        $now = now()->subMinute();
        $firstId = DB::table('invoices')->insertGetId([
            'lookup_code' => 'LOOKUP-A',
            'symbol' => 'C26TAA',
            'invoice_number' => '100',
            'tax_code' => '0312345678',
            'issued_date' => '2026-09-01',
            'invoice_type' => 'purchase',
            'name' => 'Snapshot A',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $missingId = DB::table('invoices')->insertGetId([
            'lookup_code' => 'LOOKUP-B',
            'symbol' => 'C26TAA',
            'invoice_number' => '101',
            'tax_code' => '0312345678',
            'issued_date' => '2026-09-01',
            'invoice_type' => 'purchase',
            'name' => 'Snapshot B',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('invoice_files')->insert([
            'invoice_id' => $missingId,
            'provider' => 'gdt',
            'status' => 'stored',
            'path' => 'invoices/test.pdf',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $snapshot = app(InvoiceModuleSnapshotService::class)->create('test-restore');

        DB::table('invoice_files')->where('invoice_id', $missingId)->delete();
        DB::table('invoices')->where('id', $missingId)->delete();
        DB::table('invoices')->where('id', $firstId)->update([
            'name' => 'Current A newer',
            'updated_at' => now(),
        ]);
        DB::table('invoices')->insert([
            'lookup_code' => 'LOOKUP-CURRENT-ONLY',
            'invoice_type' => 'sold',
            'name' => 'Current only',
            'created_at' => now()->addSecond(),
            'updated_at' => now()->addSecond(),
        ]);

        $result = app(InvoiceModuleRestoreService::class)->restoreMerge($snapshot['directory']);

        $this->assertSame('merge', $result['mode']);
        $this->assertSame(1, $result['result']['insertedInvoices']);
        $this->assertSame(1, $result['result']['preservedInvoices']);
        $this->assertSame(1, $result['result']['insertedFiles']);
        $this->assertSame(0, $result['partner_master_changes']);
        $this->assertTrue($result['verification']['passed']);
        $this->assertNotSame($snapshot['directory'], $result['safety_backup']['directory']);
        $this->assertSame('Current A newer', DB::table('invoices')->where('lookup_code', 'LOOKUP-A')->value('name'));
        $this->assertTrue(DB::table('invoices')->where('lookup_code', 'LOOKUP-B')->exists());
        $this->assertTrue(DB::table('invoices')->where('lookup_code', 'LOOKUP-CURRENT-ONLY')->exists());
        $restoredId = DB::table('invoices')->where('lookup_code', 'LOOKUP-B')->value('id');
        $this->assertTrue(DB::table('invoice_files')->where('invoice_id', $restoredId)->exists());
    }

    public function test_exact_safety_rollback_restores_snapshot_state_and_removes_newer_rows(): void
    {
        $now = now()->subMinute();
        $invoiceId = DB::table('invoices')->insertGetId([
            'lookup_code' => 'ROLLBACK-A',
            'symbol' => 'C26TAA',
            'invoice_number' => '500',
            'tax_code' => '0312345678',
            'issued_date' => '2026-09-01',
            'invoice_type' => 'purchase',
            'name' => 'Before restore',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('invoice_files')->insert([
            'invoice_id' => $invoiceId,
            'provider' => 'gdt',
            'status' => 'stored',
            'path' => 'invoices/rollback.pdf',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $safety = app(InvoiceModuleSnapshotService::class)->create('safety-before-restore');

        DB::table('invoices')->where('id', $invoiceId)->update(['name' => 'Changed later', 'updated_at' => now()]);
        DB::table('invoices')->insert([
            'lookup_code' => 'ROLLBACK-NEWER',
            'invoice_type' => 'sold',
            'name' => 'Newer row',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('invoice_files')->where('invoice_id', $invoiceId)->update(['path' => 'invoices/changed.pdf']);

        $result = app(InvoiceModuleRestoreService::class)->rollbackSafety($safety['directory']);

        $this->assertSame('rollback', $result['mode']);
        $this->assertTrue($result['verification']['passed']);
        $this->assertSame(0, $result['partner_master_changes']);
        $this->assertSame('Before restore', DB::table('invoices')->where('id', $invoiceId)->value('name'));
        $this->assertFalse(DB::table('invoices')->where('lookup_code', 'ROLLBACK-NEWER')->exists());
        $this->assertSame('invoices/rollback.pdf', DB::table('invoice_files')->where('invoice_id', $invoiceId)->value('path'));
        $this->assertStringStartsWith('invoices/module-backups/', $result['safety_backup']['directory']);
        $this->assertNotSame($safety['directory'], $result['safety_backup']['directory']);
    }

    public function test_exact_rollback_rejects_non_safety_snapshot(): void
    {
        DB::table('invoices')->insert([
            'lookup_code' => 'ROLLBACK-MANUAL',
            'invoice_type' => 'sold',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $snapshot = app(InvoiceModuleSnapshotService::class)->create('manual');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chỉ được phép từ Safety Backup');

        app(InvoiceModuleRestoreService::class)->rollbackSafety($snapshot['directory']);
    }

    public function test_restore_is_blocked_when_snapshot_checksum_is_invalid(): void
    {
        DB::table('invoices')->insert([
            'lookup_code' => 'LOOKUP-A',
            'invoice_type' => 'purchase',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $snapshot = app(InvoiceModuleSnapshotService::class)->create('test-invalid');
        Storage::disk('local')->put($snapshot['directory'].'/database/invoices.json', 'tampered');

        $before = DB::table('invoices')->count();

        try {
            app(InvoiceModuleRestoreService::class)->restoreMerge($snapshot['directory']);
            $this->fail('Restore should have been blocked.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('chưa đủ điều kiện restore', $exception->getMessage());
        }

        $this->assertSame($before, DB::table('invoices')->count());
    }
}

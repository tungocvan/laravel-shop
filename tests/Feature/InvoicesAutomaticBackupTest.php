<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Mail\InvoiceFilesBackupMail;
use Modules\Invoices\Services\AutomaticInvoiceBackupService;
use Tests\TestCase;

class InvoicesAutomaticBackupTest extends TestCase
{
    public function test_automatic_backup_only_sends_new_or_changed_files(): void
    {
        Schema::dropIfExists('invoice_backup_runs');
        (require base_path('Modules/Invoices/database/migrations/2026_08_15_230000_create_invoice_backup_runs_table.php'))->up();

        $base = 'invoices-test-auto-backup-'.uniqid();
        config()->set('invoices.storage.export_directory', $base);
        config()->set('invoices.backup.recipient', 'backup@example.com');
        config()->set('invoices.backup.email_chunk_bytes', 12 * 1024 * 1024);

        $folder = storage_path('app/'.$base.'/vat_in');
        mkdir($folder, 0775, true);
        $file = $folder.'/vat_in_2026-08.xlsx';
        file_put_contents($file, 'version-1');

        Mail::fake();

        try {
            $service = app(AutomaticInvoiceBackupService::class);

            $first = $service->run();
            $second = $service->run();

            $this->assertSame('success', $first->status);
            $this->assertSame(1, $first->files_count);
            $this->assertSame('skipped', $second->status);
            Mail::assertSent(InvoiceFilesBackupMail::class, 1);

            sleep(1);
            file_put_contents($file, 'version-2');
            clearstatcache(true, $file);

            $third = $service->run();
            $this->assertSame('success', $third->status);
            $this->assertSame(1, $third->files_count);
            Mail::assertSent(InvoiceFilesBackupMail::class, 2);
        } finally {
            @unlink($file);
            @rmdir($folder);
            @rmdir(dirname($folder));
            Schema::dropIfExists('invoice_backup_runs');
        }
    }
}

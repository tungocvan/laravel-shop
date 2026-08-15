<?php

namespace Modules\Invoices\Services;

use Modules\Invoices\Models\InvoiceBackupRun;
use RuntimeException;

class AutomaticInvoiceBackupService
{
    public function __construct(private readonly InvoiceFilesEmailBackupService $emailBackupService) {}

    public function run(?string $recipient = null, string $mode = 'automatic'): InvoiceBackupRun
    {
        $recipient = trim((string) ($recipient ?: config('invoices.backup.recipient')));
        if ($recipient === '') {
            throw new RuntimeException('Chưa cấu hình INVOICES_BACKUP_EMAIL.');
        }

        $run = InvoiceBackupRun::query()->create([
            'mode' => $mode,
            'status' => 'running',
            'recipient' => $recipient,
            'started_at' => now(),
        ]);

        try {
            $files = $this->pendingFiles();
            if ($files === []) {
                $run->update([
                    'status' => 'skipped',
                    'message' => 'Không có file mới hoặc file thay đổi kể từ lần backup thành công gần nhất.',
                    'finished_at' => now(),
                ]);
                return $run->fresh();
            }

            $result = $this->emailBackupService->sendFiles($recipient, $files);
            $run->update([
                'status' => 'success',
                'files_count' => $result['files_backed_up'],
                'emails_sent' => $result['emails_sent'],
                'bytes_total' => $result['bytes_total'],
                'files' => array_map(fn (array $file) => [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'mtime' => $file['mtime'],
                    'fingerprint' => $file['fingerprint'],
                ], $files),
                'message' => 'Backup tự động hoàn tất.',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
            throw $exception;
        }

        return $run->fresh();
    }

    public function pendingFiles(): array
    {
        $current = $this->emailBackupService->files();
        if ($current === []) return [];

        $known = InvoiceBackupRun::query()
            ->where('status', 'success')
            ->whereNotNull('files')
            ->get(['files'])
            ->flatMap(fn (InvoiceBackupRun $run) => collect($run->files ?: [])->pluck('fingerprint'))
            ->filter()
            ->unique()
            ->flip();

        return array_values(array_filter($current, fn (array $file) => ! $known->has($file['fingerprint'])));
    }

    public function recentRuns(int $limit = 10)
    {
        return InvoiceBackupRun::query()->latest('id')->limit($limit)->get();
    }
}

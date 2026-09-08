<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\GoogleDriveInvoiceExportService;
use RuntimeException;
use Throwable;

class ProcessGdtInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public string $start,
        public string $end,
        public bool $vatIn = false,
        public ?string $syncId = null,
    ) {}

    public function handle(GdtInvoiceService $service, GoogleDriveInvoiceExportService $drive): void
    {
        $this->updateStatus('processing', 'Worker bắt đầu xử lý.');

        Log::info('[GDT JOB] Bắt đầu xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'start' => $this->start,
            'end' => $this->end,
            'type' => $this->vatIn ? 'purchase' : 'sold',
        ]);

        $expectedFile = $service->expectedExportPath($this->start, $this->end, $this->vatIn);
        $fileName = basename($expectedFile);

        if (is_file($expectedFile) && is_readable($expectedFile)) {
            $this->appendLog('File Excel '.$fileName.' đã tồn tại trên server; bỏ qua gọi GDT.');
            $this->ensureLocalFileBackedUp($drive, $expectedFile);
            $this->completeWithoutGdt($fileName, 'local', 'File Excel đã tồn tại trên server; không cần đồng bộ lại GDT.');

            return;
        }

        if ($drive->isConnected()) {
            $this->appendLog('Không có file Excel trên server; đang kiểm tra Laravel-Backup/Invoices trên Google Drive.');

            try {
                if ($drive->exists($fileName)) {
                    $this->appendLog('Google Drive đã có '.$fileName.'; bỏ qua gọi GDT để tránh đồng bộ trùng.');
                    $this->completeWithoutGdt($fileName, 'google_drive', 'File Excel đã tồn tại trên Google Drive; không cần đồng bộ lại GDT.');

                    return;
                }
            } catch (Throwable $exception) {
                Log::warning('[GDT JOB] Không thể xác minh file hóa đơn trên Google Drive.', [
                    'sync_id' => $this->syncId,
                    'file' => $fileName,
                    'error' => $exception->getMessage(),
                ]);

                throw new RuntimeException(
                    'Không thể xác minh file trên Google Drive nên chưa gọi GDT, tránh tạo dữ liệu trùng.',
                    previous: $exception,
                );
            }

            $this->appendLog('Google Drive chưa có '.$fileName.'; bắt đầu đồng bộ từ GDT.');
        } else {
            $this->appendLog('Không có file Excel trên server và Google Drive chưa kết nối; bắt đầu đồng bộ từ GDT.');
        }

        $file = $service->processRange(
            $this->start,
            $this->end,
            fn (string $message) => $this->appendLog($message),
            $this->vatIn
        );

        if (! is_string($file) || ! is_file($file) || ! is_readable($file)) {
            throw new RuntimeException('Đồng bộ kết thúc nhưng không tạo được file Excel trên server.');
        }

        $this->uploadToGoogleDrive($drive, $file);

        $this->updateStatus('completed', 'Đồng bộ hoàn tất và file Excel đã được tạo.', [
            'file' => basename($file),
            'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
            'source' => 'gdt',
            'sync_skipped' => false,
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::info('[GDT JOB] Hoàn tất xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'file' => $file,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->updateStatus('failed', 'Đồng bộ thất bại: '.$exception->getMessage(), [
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::error('[GDT JOB] Xử lý hóa đơn thất bại.', [
            'sync_id' => $this->syncId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function completeWithoutGdt(string $fileName, string $source, string $message): void
    {
        $this->updateStatus('completed', $message, [
            'file' => $fileName,
            'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
            'source' => $source,
            'sync_skipped' => true,
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::info('[GDT JOB] Bỏ qua gọi GDT vì file đồng bộ đã tồn tại.', [
            'sync_id' => $this->syncId,
            'file' => $fileName,
            'source' => $source,
        ]);
    }

    private function ensureLocalFileBackedUp(GoogleDriveInvoiceExportService $drive, string $file): void
    {
        if (! $drive->isConnected()) {
            $this->appendLog('Google Drive chưa kết nối; giữ file hiện có trên server.');

            return;
        }

        try {
            if ($drive->exists(basename($file))) {
                $this->appendLog('Google Drive cũng đã có '.basename($file).'; không upload lại.');

                return;
            }

            $this->appendLog('Google Drive chưa có file này; đang sao lưu file local vào Laravel-Backup/Invoices.');
            $this->uploadToGoogleDrive($drive, $file);
        } catch (Throwable $exception) {
            $this->appendLog('Không thể kiểm tra/sao lưu Google Drive; file Excel trên server vẫn được giữ lại.');

            Log::warning('[GDT JOB] Không thể đảm bảo backup Google Drive cho file local.', [
                'sync_id' => $this->syncId,
                'file' => basename($file),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function uploadToGoogleDrive(GoogleDriveInvoiceExportService $drive, string $file): void
    {
        if (! $drive->isConnected()) {
            $this->appendLog('Google Drive chưa kết nối, bỏ qua upload tự động.');

            return;
        }

        $this->appendLog('Google Drive đã kết nối, đang upload file vào Laravel-Backup/Invoices.');

        try {
            $uploaded = $drive->upload($file);

            $this->appendLog('Google Drive: đã upload '.($uploaded['name'] ?? basename($file)).' vào Laravel-Backup/Invoices.');

            Log::info('[GDT JOB] Đã upload file hóa đơn lên Google Drive.', [
                'sync_id' => $this->syncId,
                'file' => basename($file),
                'drive_file_id' => $uploaded['id'] ?? null,
                'updated_existing' => $uploaded['updated_existing'] ?? false,
            ]);
        } catch (Throwable $exception) {
            $this->appendLog('Google Drive: upload tự động thất bại; file Excel trên server vẫn được giữ lại.');

            Log::warning('[GDT JOB] Upload file hóa đơn lên Google Drive thất bại.', [
                'sync_id' => $this->syncId,
                'file' => basename($file),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function statusKey(): ?string
    {
        return $this->syncId ? 'invoices:gdt-sync:'.$this->syncId : null;
    }

    private function appendLog(string $message): void
    {
        $key = $this->statusKey();
        if (! $key) {
            return;
        }

        $status = Cache::get($key, []);
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        Cache::put($key, $status, now()->addHours(24));
    }

    private function updateStatus(string $state, string $message, array $extra = []): void
    {
        $key = $this->statusKey();
        if (! $key) {
            return;
        }

        $status = Cache::get($key, []);
        $status['state'] = $state;
        $status['message'] = $message;
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        $status = array_merge($status, $extra);

        Cache::put($key, $status, now()->addHours(24));
    }
}

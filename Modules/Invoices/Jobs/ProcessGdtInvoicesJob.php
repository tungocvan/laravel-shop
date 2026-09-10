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
use Modules\Invoices\Services\InvoiceSourceCoverageService;
use RuntimeException;
use Throwable;

class ProcessGdtInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180];

    public int $timeout = 600;

    public function __construct(
        public string $start,
        public string $end,
        public bool $vatIn = false,
        public ?string $syncId = null,
    ) {}

    public function handle(
        GdtInvoiceService $service,
        GoogleDriveInvoiceExportService $drive,
        InvoiceSourceCoverageService $coverage,
    ): void {
        $this->updateStatus('processing', 'Worker bắt đầu xử lý.');

        Log::info('[GDT JOB] Bắt đầu xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'start' => $this->start,
            'end' => $this->end,
            'type' => $this->vatIn ? 'purchase' : 'sold',
            'attempt' => $this->attempts(),
        ]);

        $expectedFile = $service->expectedExportPath($this->start, $this->end, $this->vatIn);
        $fileName = basename($expectedFile);
        $sourceCoverage = $coverage->coverage($this->start, $this->end, $this->vatIn);
        $canonicalReady = (bool) $sourceCoverage['complete'];

        $this->appendCoverage('RAW canonical hiện có', $sourceCoverage);

        if (is_file($expectedFile) && is_readable($expectedFile) && $canonicalReady) {
            $this->appendLog('File Excel và RAW canonical đều đầy đủ; bỏ qua gọi GDT.');
            $this->completeWithoutGdt($fileName, 'local', 'Dữ liệu nguồn canonical đã đầy đủ; không cần đồng bộ lại GDT.');

            return;
        }

        /*
         * Historical recovery path:
         * local invoice headers may already exist while the new canonical RAW store is only
         * partially populated. Recover missing detail directly by invoice identity first.
         * Inventory depends on detail; missing historical RAW header must not block this repair.
         */
        if ($this->vatIn
            && (int) $sourceCoverage['total'] > 0
            && (int) $sourceCoverage['detail_ready'] < (int) $sourceCoverage['total']) {
            $detailRecovery = $service->recoverMissingDetailsFromLocalRange(
                $this->start,
                $this->end,
                fn (string $message) => $this->appendLog($message),
                true,
            );

            $this->appendLog(sprintf(
                '[RAW] Recovery detail local: ứng viên %d · tải mới %d · đã có %d · lỗi %d.',
                $detailRecovery['candidates'],
                $detailRecovery['fetched'],
                $detailRecovery['reused'],
                $detailRecovery['failed'],
            ));

            $sourceCoverage = $coverage->coverage($this->start, $this->end, $this->vatIn);
            $canonicalReady = (bool) $sourceCoverage['complete'];
            $this->appendCoverage('RAW canonical sau recovery detail', $sourceCoverage);
        }

        $detailReady = (int) $sourceCoverage['total'] > 0
            && (int) $sourceCoverage['detail_ready'] === (int) $sourceCoverage['total'];

        if (is_file($expectedFile) && is_readable($expectedFile) && $detailReady && ! $canonicalReady) {
            $this->appendLog('RAW detail đã đầy đủ cho nghiệp vụ downstream; RAW header lịch sử còn thiếu và sẽ bổ sung khi API danh sách GDT ổn định.');
            $this->updateStatus('completed', 'Recovery RAW detail hoàn tất. Inventory có thể chuẩn hóa từ local; RAW header sẽ được bổ sung ở lần đồng bộ GDT sau.', [
                'file' => $fileName,
                'direction' => 'vat_in',
                'source' => 'local_detail_recovery',
                'sync_skipped' => true,
                'source_header_pending' => true,
                'no_data' => false,
                'finished_at' => now()->toIso8601String(),
            ]);

            return;
        }

        if (is_file($expectedFile) && is_readable($expectedFile) && ! $canonicalReady) {
            $this->appendLog('File Excel đã tồn tại nhưng RAW canonical chưa đầy đủ; tiếp tục gọi GDT để hoàn thiện phần còn thiếu.');
        }

        if ($drive->isConnected()) {
            try {
                if ($drive->exists($fileName) && $canonicalReady) {
                    $this->appendLog('Google Drive đã có file và RAW canonical local đầy đủ; bỏ qua gọi GDT.');
                    $this->completeWithoutGdt($fileName, 'google_drive', 'Dữ liệu nguồn canonical đã đầy đủ; không cần đồng bộ lại GDT.');

                    return;
                }

                if ($drive->exists($fileName) && ! $canonicalReady) {
                    $this->appendLog('Google Drive đã có file nhưng RAW canonical local chưa đầy đủ; không dùng file backup làm lý do bỏ qua GDT.');
                }
            } catch (Throwable $exception) {
                Log::warning('[GDT JOB] Không thể xác minh file hóa đơn trên Google Drive.', [
                    'sync_id' => $this->syncId,
                    'file' => $fileName,
                    'error' => $exception->getMessage(),
                ]);

                $this->appendLog('Không xác minh được file Google Drive; tiếp tục dựa trên trạng thái RAW canonical local.');
            }
        }

        $file = $service->processRange(
            $this->start,
            $this->end,
            fn (string $message) => $this->appendLog($message),
            $this->vatIn
        );

        if ($file === null) {
            $this->updateStatus('completed', 'Không có hóa đơn trong khoảng thời gian đã chọn. Hệ thống không tạo file Excel.', [
                'file' => null,
                'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
                'source' => 'gdt',
                'sync_skipped' => false,
                'no_data' => true,
                'finished_at' => now()->toIso8601String(),
            ]);

            return;
        }

        if (! is_file($file) || ! is_readable($file)) {
            throw new RuntimeException('Đồng bộ kết thúc nhưng không tạo được file Excel trên server.');
        }

        $finalCoverage = $coverage->coverage($this->start, $this->end, $this->vatIn);
        $this->appendCoverage('RAW canonical sau đồng bộ', $finalCoverage);

        $this->updateStatus('completed', 'Đồng bộ hoàn tất: dữ liệu hóa đơn và RAW canonical đã được lưu trên server.', [
            'file' => basename($file),
            'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
            'source' => 'gdt',
            'sync_skipped' => false,
            'no_data' => false,
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::info('[GDT JOB] Hoàn tất xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'file' => $file,
            'coverage' => $finalCoverage,
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

    private function appendCoverage(string $label, array $coverage): void
    {
        $this->appendLog(sprintf(
            '%s: header %d/%d · detail %d/%d.',
            $label,
            $coverage['header_ready'],
            $coverage['total'],
            $coverage['detail_ready'],
            $coverage['total'],
        ));
    }

    private function completeWithoutGdt(string $fileName, string $source, string $message): void
    {
        $this->updateStatus('completed', $message, [
            'file' => $fileName,
            'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
            'source' => $source,
            'sync_skipped' => true,
            'no_data' => false,
            'finished_at' => now()->toIso8601String(),
        ]);
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

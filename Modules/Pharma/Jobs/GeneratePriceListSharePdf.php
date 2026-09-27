<?php

namespace Modules\Pharma\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\Pharma\Models\PriceListExportShare;
use RuntimeException;
use Throwable;

final class GeneratePriceListSharePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public int $shareId) {}

    public function handle(): void
    {
        $share = PriceListExportShare::query()->findOrFail($this->shareId);
        $disk = Storage::disk('local');

        if (! $share->storage_path || ! $disk->exists($share->storage_path)) {
            $share->update([
                'pdf_status' => 'failed',
                'pdf_error_message' => 'File Excel nguồn không còn tồn tại.',
            ]);

            return;
        }

        $share->update([
            'pdf_status' => 'processing',
            'pdf_error_message' => null,
        ]);

        $workDir = storage_path('app/tmp/pharma-price-list-pdf/'.$share->id);
        if (! is_dir($workDir) && ! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
            throw new RuntimeException('Không thể tạo thư mục tạm chuyển PDF.');
        }

        try {
            $source = $disk->path($share->storage_path);
            $result = Process::timeout(100)->run([
                'libreoffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $workDir,
                $source,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'LibreOffice convert PDF thất bại.');
            }

            $generated = $workDir.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
            if (! is_file($generated)) {
                throw new RuntimeException('Không tìm thấy file PDF sau khi chuyển đổi.');
            }

            $pdfName = pathinfo($share->download_name, PATHINFO_FILENAME).'.pdf';
            $pdfPath = dirname($share->storage_path).'/'.pathinfo($share->storage_path, PATHINFO_FILENAME).'.pdf';
            $contents = file_get_contents($generated);
            if ($contents === false || ! $disk->put($pdfPath, $contents) || ! $disk->exists($pdfPath)) {
                throw new RuntimeException('Không thể lưu file PDF vào storage.');
            }

            $this->normalizeStorageAccess($disk->path($pdfPath));

            $share->update([
                'pdf_status' => 'completed',
                'pdf_storage_path' => $pdfPath,
                'pdf_download_name' => $pdfName,
                'pdf_error_message' => null,
                'pdf_completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $share->update([
                'pdf_status' => 'failed',
                'pdf_error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            throw $e;
        } finally {
            foreach (glob($workDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($workDir);
        }
    }

    private function normalizeStorageAccess(string $filePath): void
    {
        @chgrp($filePath, 'www-data');
        @chmod($filePath, 0664);

        $storageApp = rtrim(storage_path('app'), DIRECTORY_SEPARATOR);
        $directory = dirname($filePath);
        while (str_starts_with($directory, $storageApp) && $directory !== $storageApp) {
            @chgrp($directory, 'www-data');
            @chmod($directory, 0775);
            $directory = dirname($directory);
        }
    }
}

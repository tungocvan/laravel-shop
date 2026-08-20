<?php

namespace Modules\ClientPortal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Models\PriceListExport;
use RuntimeException;

class GeneratePriceListPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public string $exportId) {}

    public function handle(): void
    {
        $export = PriceListExport::findOrFail($this->exportId);
        if ($export->status !== 'completed' || ! $export->file_path || ! Storage::disk('local')->exists($export->file_path)) {
            $export->update(['pdf_status' => 'failed', 'pdf_error_message' => 'File Excel nguồn không còn tồn tại.']);
            return;
        }

        $export->update(['pdf_status' => 'processing', 'pdf_error_message' => null]);
        $workDir = storage_path('app/tmp/price-list-pdf/'.$export->id);
        if (! is_dir($workDir) && ! mkdir($workDir, 0775, true) && ! is_dir($workDir)) {
            throw new RuntimeException('Không thể tạo thư mục tạm chuyển PDF.');
        }

        try {
            $source = Storage::disk('local')->path($export->file_path);
            $result = Process::timeout(100)->run(['libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', $workDir, $source]);
            if (! $result->successful()) throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'LibreOffice convert PDF thất bại.');

            $generated = $workDir.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
            if (! is_file($generated)) throw new RuntimeException('Không tìm thấy file PDF sau khi chuyển đổi.');

            $pdfName = pathinfo((string) ($export->file_name ?: basename($source)), PATHINFO_FILENAME).'.pdf';
            $pdfPath = 'client-portal/price-lists/'.$export->user_id.'/'.$export->id.'/'.$pdfName;
            Storage::disk('local')->put($pdfPath, file_get_contents($generated));
            $export->update(['pdf_status' => 'completed', 'pdf_path' => $pdfPath, 'pdf_name' => $pdfName, 'pdf_error_message' => null]);
        } catch (\Throwable $e) {
            $export->update(['pdf_status' => 'failed', 'pdf_error_message' => mb_substr($e->getMessage(), 0, 2000)]);
            throw $e;
        } finally {
            foreach (glob($workDir.'/*') ?: [] as $file) @unlink($file);
            @rmdir($workDir);
        }
    }
}

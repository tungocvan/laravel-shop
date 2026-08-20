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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            $conversionSource = $this->prepareForLibreOffice($source, $workDir);
            $result = Process::timeout(100)->run([
                'libreoffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $workDir,
                $conversionSource,
            ]);
            if (! $result->successful()) {
                throw new RuntimeException(trim($result->errorOutput() ?: $result->output()) ?: 'LibreOffice convert PDF thất bại.');
            }

            $generated = $workDir.'/'.pathinfo($conversionSource, PATHINFO_FILENAME).'.pdf';
            if (! is_file($generated)) {
                throw new RuntimeException('Không tìm thấy file PDF sau khi chuyển đổi.');
            }

            $pdfName = pathinfo((string) ($export->file_name ?: basename($source)), PATHINFO_FILENAME).'.pdf';
            $pdfPath = 'client-portal/price-lists/'.$export->user_id.'/'.$export->id.'/'.$pdfName;
            $disk = Storage::disk('local');
            $directory = dirname($pdfPath);
            $disk->makeDirectory($directory);

            $contents = file_get_contents($generated);
            if ($contents === false) {
                throw new RuntimeException('Không thể đọc file PDF vừa chuyển đổi.');
            }

            $written = $disk->put($pdfPath, $contents);
            if (! $written || ! $disk->exists($pdfPath)) {
                throw new RuntimeException('Không thể lưu file PDF vào storage.');
            }

            // Queue workers may run as root while PHP-FPM serves downloads as www-data.
            // Normalize the generated file and every price-list directory so the web
            // process can traverse/read the PDF without requiring a manual chmod/chown.
            $this->normalizeStorageAccess($disk->path($pdfPath));

            $export->update([
                'pdf_status' => 'completed',
                'pdf_path' => $pdfPath,
                'pdf_name' => $pdfName,
                'pdf_error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $export->update([
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

    /**
     * LibreOffice and Excel calculate automatic wrapped-row heights differently.
     * Floating drawings below those rows (notably the signature/stamp) can therefore
     * move upward into the table during headless PDF conversion. Build a temporary
     * XLSX with explicit table-row heights so both renderers use the same geometry.
     */
    private function prepareForLibreOffice(string $source, string $workDir): string
    {
        $spreadsheet = IOFactory::load($source);
        $sheet = $spreadsheet->getActiveSheet();
        $this->freezeWrappedTableRowHeights($sheet);

        $normalized = $workDir.'/price-list-pdf-source.xlsx';
        (new Xlsx($spreadsheet))->save($normalized);
        $spreadsheet->disconnectWorksheets();

        return $normalized;
    }

    private function freezeWrappedTableRowHeights($sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($row = 1; $row <= $highestRow; $row++) {
            $nonEmpty = 0;
            $maxLines = 1;

            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $cell = $sheet->getCell([$column, $row]);
                $value = $cell->getFormattedValue();
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                $nonEmpty++;
                $letter = Coordinate::stringFromColumnIndex($column);
                $width = (float) $sheet->getColumnDimension($letter)->getWidth();
                if ($width <= 0) {
                    $width = (float) $sheet->getDefaultColumnDimension()->getWidth();
                }

                $pixels = max(24, SharedDrawing::cellDimensionToPixels(
                    $width,
                    $sheet->getParent()->getDefaultStyle()->getFont()
                ));
                $charactersPerLine = max(3, (int) floor($pixels / 7.2));
                $lines = 0;
                foreach (preg_split('/\R/u', (string) $value) ?: [''] as $textLine) {
                    $length = max(1, mb_strlen($textLine));
                    $lines += max(1, (int) ceil($length / $charactersPerLine));
                }
                $maxLines = max($maxLines, $lines);
            }

            // Header and table data have many populated cells. Footer/header merged rows
            // intentionally stay untouched because their height is already deterministic.
            if ($nonEmpty < 3) {
                continue;
            }

            $points = min(120, max(20, 8 + ($maxLines * 13.5)));
            $sheet->getRowDimension($row)->setRowHeight($points);
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

        // storage/app itself must remain traversable by the web process as well.
        @chgrp($storageApp, 'www-data');
        @chmod($storageApp, 0775);
    }
}

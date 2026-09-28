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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    /**
     * LibreOffice calculates automatic wrapped-row heights differently from Excel.
     * Normalize only a temporary conversion workbook so the downloadable Excel stays
     * untouched while PDF pagination keeps table rows and the signature block stable.
     */
    private function prepareForLibreOffice(string $source, string $workDir): string
    {
        $spreadsheet = IOFactory::load($source);
        $sheet = $spreadsheet->getActiveSheet();

        $this->freezeWrappedTableRowHeights($sheet);
        $this->rasterizeSignatureFooterForLibreOffice($sheet, $workDir);
        $this->keepSignatureFooterTogether($sheet);
        $this->stabilizePrintLayout($sheet);

        $normalized = $workDir.'/pharma-price-list-pdf-source.xlsx';
        (new Xlsx($spreadsheet))->save($normalized);
        $spreadsheet->disconnectWorksheets();

        return $normalized;
    }

    private function freezeWrappedTableRowHeights($sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($row = 1; $row <= $highestRow; $row++) {
            if ($sheet->getRowDimension($row)->getRowHeight() > 0) {
                continue;
            }

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
                    $lines += max(1, (int) ceil(max(1, mb_strlen($textLine)) / $charactersPerLine));
                }
                $maxLines = max($maxLines, $lines);
            }

            // Dense rows are the product table. Sparse/merged rows belong to document
            // headers and signature/footer areas and keep their authored geometry.
            if ($nonEmpty < 3) {
                continue;
            }

            $sheet->getRowDimension($row)->setRowHeight(min(120, max(20, 8 + ($maxLines * 13.5))));
        }
    }

    /**
     * LibreOffice can reposition an XLSX signature drawing independently from the
     * footer cells when pagination changes. For the temporary PDF workbook only,
     * flatten the complete authored footer into one PNG and replace the original
     * cells/drawing with that single visual block.
     */
    private function rasterizeSignatureFooterForLibreOffice(Worksheet $sheet, string $workDir): void
    {
        $markerRow = $this->footerMarkerRow($sheet);
        if ($markerRow === null || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $signatureIndex = null;
        $signature = null;
        foreach ($sheet->getDrawingCollection() as $index => $drawing) {
            if (strcasecmp((string) $drawing->getName(), 'Signature') === 0) {
                $signatureIndex = $index;
                $signature = $drawing;
                break;
            }
        }
        if ($signature === null) {
            return;
        }

        $locationRow = $markerRow + 1;
        $titleRow = $markerRow + 2;
        $signatureRow = $markerRow + 3;
        $nameRow = $markerRow + 6;
        $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $firstColumnIndex = max(1, $lastColumnIndex - 4);
        $firstColumn = Coordinate::stringFromColumnIndex($firstColumnIndex);
        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);

        $location = (string) $sheet->getCell("{$firstColumn}{$locationRow}")->getFormattedValue();
        $title = (string) $sheet->getCell("{$firstColumn}{$titleRow}")->getFormattedValue();
        $name = (string) $sheet->getCell("{$firstColumn}{$nameRow}")->getFormattedValue();
        $signatureBytes = @file_get_contents($signature->getPath());
        if ($signatureBytes === false) {
            return;
        }
        $signatureImage = @imagecreatefromstring($signatureBytes);
        if ($signatureImage === false) {
            return;
        }

        $canvasWidth = 900;
        $canvasHeight = 390;
        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 20, 20, 20);
        imagefilledrectangle($canvas, 0, 0, $canvasWidth, $canvasHeight, $white);

        $font = $this->pdfFooterFont();
        $this->drawCenteredFooterText($canvas, $location, 20, 34, $black, $font, false);
        $this->drawCenteredFooterText($canvas, $title, 22, 70, $black, $font, true);

        $sourceWidth = imagesx($signatureImage);
        $sourceHeight = imagesy($signatureImage);
        $targetHeight = 220;
        $targetWidth = max(1, (int) round($sourceWidth * ($targetHeight / max(1, $sourceHeight))));
        if ($targetWidth > 420) {
            $targetWidth = 420;
            $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / max(1, $sourceWidth))));
        }
        $targetX = (int) round(($canvasWidth - $targetWidth) / 2);
        imagecopyresampled($canvas, $signatureImage, $targetX, 88, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($signatureImage);

        $this->drawCenteredFooterText($canvas, $name, 21, 350, $black, $font, true);

        $footerPng = $workDir.'/pharma-price-list-footer.png';
        imagepng($canvas, $footerPng, 6);
        imagedestroy($canvas);

        // Remove the original floating signature and clear the authored footer text:
        // the PNG below is now the sole PDF representation of this block.
        if ($signatureIndex !== null) {
            $sheet->getDrawingCollection()->offsetUnset($signatureIndex);
        }
        foreach ([$locationRow, $titleRow, $nameRow] as $row) {
            $sheet->setCellValue("{$firstColumn}{$row}", null);
        }

        $footerEndRow = $signatureRow + 4;
        $sheet->mergeCells("{$firstColumn}{$locationRow}:{$lastColumn}{$footerEndRow}");
        foreach (range($locationRow, $footerEndRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(42);
        }

        $flattened = new Drawing();
        $flattened->setName('PDF Footer');
        $flattened->setDescription('Flattened signature footer for LibreOffice PDF conversion');
        $flattened->setPath($footerPng);
        $flattened->setCoordinates("{$firstColumn}{$locationRow}");
        $flattened->setOffsetX(0);
        $flattened->setOffsetY(0);
        $flattened->setResizeProportional(true);
        $flattened->setHeight(290);
        $flattened->setWorksheet($sheet);
    }

    private function pdfFooterFont(): ?string
    {
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
        ] as $font) {
            if (is_file($font)) {
                return $font;
            }
        }

        return null;
    }

    private function drawCenteredFooterText($image, string $text, int $size, int $baseline, int $color, ?string $font, bool $bold): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($font !== null && function_exists('imagettftext')) {
            $fontSize = $bold ? $size + 1 : $size;
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $width = $box === false ? 0 : abs($box[2] - $box[0]);
            $x = max(0, (int) round((imagesx($image) - $width) / 2));
            imagettftext($image, $fontSize, 0, $x, $baseline, $color, $font, $text);
            if ($bold) {
                imagettftext($image, $fontSize, 0, $x + 1, $baseline, $color, $font, $text);
            }
            return;
        }

        $fontId = 5;
        $width = imagefontwidth($fontId) * strlen($text);
        imagestring($image, $fontId, max(0, (int) round((imagesx($image) - $width) / 2)), max(0, $baseline - 15), $text, $color);
    }

    private function footerMarkerRow(Worksheet $sheet): ?int
    {
        for ($row = $sheet->getHighestRow(); $row >= 1; $row--) {
            if ((string) $sheet->getCell("A{$row}")->getValue() === '__PHARMA_PRICE_LIST_FOOTER__') {
                return $row;
            }
        }

        return null;
    }

    private function keepSignatureFooterTogether(Worksheet $sheet): void
    {
        $markerRow = $this->footerMarkerRow($sheet);

        if ($markerRow === null) {
            return;
        }

        // The marker row is hidden in Excel and only carries layout metadata.
        // A manual row break before it forces LibreOffice to start the complete
        // footer/signature block on a fresh page instead of floating the drawing
        // beside the last product rows.
        $sheet->setBreak("A{$markerRow}", Worksheet::BREAK_ROW);
        $sheet->setCellValue("A{$markerRow}", null);
    }

    private function stabilizePrintLayout($sheet): void
    {
        $setup = $sheet->getPageSetup();

        // Preserve an authored print area. Otherwise constrain the conversion to the
        // workbook's used range so LibreOffice does not invent extra printable pages.
        if (! $setup->getPrintArea()) {
            $setup->setPrintArea('A1:'.$sheet->getHighestDataColumn().$sheet->getHighestDataRow());
        }

        if ($setup->getOrientation() === PageSetup::ORIENTATION_DEFAULT) {
            $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        }

        if ($setup->getPaperSize() === PageSetup::PAPERSIZE_DEFAULT) {
            $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        }

        // One page wide, unlimited pages tall: keep all columns together while allowing
        // the table/footer to flow to the next page instead of shrinking into overlap.
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
        $setup->setHorizontalCentered(true);
        $setup->setVerticalCentered(false);
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

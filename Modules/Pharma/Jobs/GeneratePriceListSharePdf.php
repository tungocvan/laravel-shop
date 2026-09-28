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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use setasign\Fpdi\Fpdi;
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
            [$conversionSource, $pdfFooter] = $this->prepareForLibreOffice($source, $workDir);
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

            if ($pdfFooter !== null) {
                $generated = $this->stampPdfFooter($generated, $pdfFooter, $workDir);
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
    private function prepareForLibreOffice(string $source, string $workDir): array
    {
        $spreadsheet = IOFactory::load($source);
        $sheet = $spreadsheet->getActiveSheet();

        $this->freezeWrappedTableRowHeights($sheet);
        $pdfFooter = $this->extractPdfFooter($sheet, $workDir);
        $this->keepSignatureFooterTogether($sheet);
        $this->stabilizePrintLayout($sheet);

        $normalized = $workDir.'/pharma-price-list-pdf-source.xlsx';
        (new Xlsx($spreadsheet))->save($normalized);
        $spreadsheet->disconnectWorksheets();

        return [$normalized, $pdfFooter];
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
     * Extract footer content before LibreOffice conversion, then remove it from the
     * temporary workbook. The final footer is stamped directly onto the last PDF page.
     */
    private function extractPdfFooter(Worksheet $sheet, string $workDir): ?array
    {
        $markerRow = $this->footerMarkerRow($sheet);
        if ($markerRow === null) {
            return null;
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

        $locationRow = $markerRow + 1;
        $titleRow = $markerRow + 2;
        $signatureRow = $markerRow + 3;
        $nameRow = $markerRow + 6;
        $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $firstColumnIndex = max(1, $lastColumnIndex - 4);
        $firstColumn = Coordinate::stringFromColumnIndex($firstColumnIndex);
        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);

        $footer = [
            'location' => (string) $sheet->getCell("{$firstColumn}{$locationRow}")->getFormattedValue(),
            'title' => (string) $sheet->getCell("{$firstColumn}{$titleRow}")->getFormattedValue(),
            'name' => (string) $sheet->getCell("{$firstColumn}{$nameRow}")->getFormattedValue(),
            'signature' => null,
        ];

        if ($signature !== null) {
            $bytes = @file_get_contents($signature->getPath());
            if ($bytes !== false) {
                $extension = strtolower(pathinfo($signature->getPath(), PATHINFO_EXTENSION)) ?: 'png';
                $signaturePath = $workDir.'/pharma-price-list-signature.'.$extension;
                if (file_put_contents($signaturePath, $bytes) !== false) {
                    $footer['signature'] = $signaturePath;
                }
            }
        }

        if ($signatureIndex !== null) {
            $sheet->getDrawingCollection()->offsetUnset($signatureIndex);
        }

        // LibreOffice must not render any part of the signature footer. Preserve the
        // rows as whitespace so table pagination remains close to the authored Excel.
        foreach (range($locationRow, $nameRow) as $row) {
            foreach (range($firstColumnIndex, $lastColumnIndex) as $column) {
                $sheet->setCellValue([$column, $row], null);
            }
        }

        return $footer;
    }

    private function stampPdfFooter(string $sourcePdf, array $footer, string $workDir): string
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePdf);
        $signature = $footer['signature'] ?? null;

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($template);

            if ($page !== $pageCount) {
                continue;
            }

            $blockWidth = min(92.0, $size['width'] * 0.34);
            $x = $size['width'] - $blockWidth - 12.0;
            $bottom = 12.0;
            $nameY = $size['height'] - $bottom - 6.0;
            $imageHeight = 28.0;
            $imageY = $nameY - $imageHeight - 7.0;
            $titleY = $imageY - 10.0;
            $locationY = $titleY - 7.0;

            $pdf->SetTextColor(20, 20, 20);
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetXY($x, $locationY);
            $pdf->Cell($blockWidth, 5, $this->fpdfText((string) ($footer['location'] ?? '')), 0, 0, 'C');

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($x, $titleY);
            $pdf->Cell($blockWidth, 5, $this->fpdfText((string) ($footer['title'] ?? '')), 0, 0, 'C');

            if (is_string($signature) && is_file($signature)) {
                [$imageWidthPx, $imageHeightPx] = getimagesize($signature) ?: [1, 1];
                $imageWidth = min(48.0, $imageHeight * ($imageWidthPx / max(1, $imageHeightPx)));
                $pdf->Image($signature, $x + (($blockWidth - $imageWidth) / 2), $imageY, $imageWidth, $imageHeight);
            }

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($x, $nameY);
            $pdf->Cell($blockWidth, 5, $this->fpdfText((string) ($footer['name'] ?? '')), 0, 0, 'C');
        }

        $stamped = $workDir.'/pharma-price-list-final.pdf';
        $pdf->Output('F', $stamped);

        return $stamped;
    }

    private function fpdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);

        return $converted === false ? $text : $converted;
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

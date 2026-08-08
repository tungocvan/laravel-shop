<?php

namespace Modules\Pharma\Services\Spreadsheet;

use Modules\Pharma\DTOs\PriceListOptions;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class PriceListWorkbookBuilder
{
    public function build(
        string $sourcePath,
        string $sheetName,
        int $headerRow,
        PriceListOptions $options,
    ): string {
        $sourceBook = IOFactory::load($sourcePath);
        $source = $sourceBook->getSheetByName($sheetName);

        if (! $source) {
            throw new RuntimeException("Không tìm thấy sheet: {$sheetName}");
        }

        $targetBook = new Spreadsheet;
        $target = $targetBook->getActiveSheet();
        $target->setTitle($source->getTitle());

        $this->copyPageHeader($source, $target, $headerRow, count($options->sourceColumns), $options->recipient);
        $this->copyTable($source, $target, $headerRow, $options);
        $this->placeSignature($source, $target, $headerRow, count($options->productRows), count($options->sourceColumns), $options);
        $this->copyDrawing($source, $target);
        $this->configurePrint($target, $headerRow, count($options->productRows), count($options->sourceColumns));

        $directory = dirname($options->outputPath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu bảng giá.');
        }

        IOFactory::createWriter($targetBook, 'Xlsx')->save($options->outputPath);

        return $options->outputPath;
    }

    private function copyPageHeader(
        Worksheet $source,
        Worksheet $target,
        int $headerRow,
        int $targetColumnCount,
        string $recipient,
    ): void {
        $lastColumn = Coordinate::stringFromColumnIndex($targetColumnCount);
        $textColumnIndex = min(4, max(2, $targetColumnCount));

        for ($row = 1; $row < $headerRow; $row++) {
            $target->getRowDimension($row)->setRowHeight($source->getRowDimension($row)->getRowHeight());
        }

        // Thông tin công ty luôn được giữ dù người dùng loại cột D khỏi bảng.
        for ($row = 1; $row <= min(5, $headerRow - 1); $row++) {
            $sourceCell = $this->firstNonEmptyCell($source, $row);
            if (! $sourceCell) {
                continue;
            }

            $coordinate = Coordinate::stringFromColumnIndex($textColumnIndex).$row;
            $target->setCellValue($coordinate, $sourceCell->getValue());
            $this->copyStyle($sourceCell, $target, $coordinate);
        }

        foreach ([6, 7, 8] as $row) {
            if ($row >= $headerRow) {
                continue;
            }

            $sourceCell = $this->firstNonEmptyCell($source, $row);
            if (! $sourceCell) {
                continue;
            }

            $value = $row === 7 && trim($recipient) !== ''
                ? 'Kính gửi: '.trim($recipient)
                : $sourceCell->getValue();

            $range = "A{$row}:{$lastColumn}{$row}";
            $target->mergeCells($range);
            $target->setCellValue("A{$row}", $value);
            $this->copyStyle($sourceCell, $target, "A{$row}");
            $target->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function copyTable(Worksheet $source, Worksheet $target, int $headerRow, PriceListOptions $options): void
    {
        foreach ($options->sourceColumns as $targetIndex => $sourceLetter) {
            $targetIndex++;
            $targetLetter = Coordinate::stringFromColumnIndex($targetIndex);
            $sourceIndex = Coordinate::columnIndexFromString($sourceLetter);

            $target->getColumnDimension($targetLetter)
                ->setWidth($source->getColumnDimension($sourceLetter)->getWidth());

            $this->copyCell($source, $target, $sourceIndex, $headerRow, $targetIndex, $headerRow);
        }

        $target->getRowDimension($headerRow)->setRowHeight($source->getRowDimension($headerRow)->getRowHeight());

        foreach (array_values($options->productRows) as $offset => $sourceRow) {
            $targetRow = $headerRow + 1 + $offset;
            $target->getRowDimension($targetRow)->setRowHeight($source->getRowDimension($sourceRow)->getRowHeight());

            foreach ($options->sourceColumns as $targetIndex => $sourceLetter) {
                $targetIndex++;
                $sourceIndex = Coordinate::columnIndexFromString($sourceLetter);
                $this->copyCell($source, $target, $sourceIndex, $sourceRow, $targetIndex, $targetRow);

                if ($sourceLetter === 'A') {
                    $target->setCellValue([$targetIndex, $targetRow], $offset + 1);
                }
            }
        }
    }

    private function placeSignature(
        Worksheet $source,
        Worksheet $target,
        int $headerRow,
        int $productCount,
        int $columnCount,
        PriceListOptions $options,
    ): void {
        $dateRow = $headerRow + $productCount + 2;
        $titleRow = $dateRow + 1;
        $blankSignatureRow = $titleRow + 1;
        $startIndex = max(1, (int) floor($columnCount * 0.55));
        $startColumn = Coordinate::stringFromColumnIndex($startIndex);
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        foreach ([$dateRow, $titleRow, $blankSignatureRow] as $row) {
            $target->mergeCells("{$startColumn}{$row}:{$lastColumn}{$row}");
            $target->getStyle("{$startColumn}{$row}:{$lastColumn}{$row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $target->setCellValue("{$startColumn}{$dateRow}", $options->signatureDate);
        $target->setCellValue("{$startColumn}{$titleRow}", $options->signatureTitle);

        foreach ([56 => $dateRow, 57 => $titleRow, 58 => $blankSignatureRow] as $sourceRow => $targetRow) {
            $sourceCell = $this->firstNonEmptyCell($source, $sourceRow) ?? $source->getCell("K{$sourceRow}");
            $this->copyStyle($sourceCell, $target, "{$startColumn}{$targetRow}");
            $target->getRowDimension($targetRow)->setRowHeight($source->getRowDimension($sourceRow)->getRowHeight());
        }
    }

    private function configurePrint(Worksheet $target, int $headerRow, int $productCount, int $columnCount): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $lastRow = $headerRow + $productCount + 4;

        $target->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow)
            ->setPrintArea("A1:{$lastColumn}{$lastRow}");
        $target->getPageMargins()
            ->setTop(0.35)
            ->setRight(0.25)
            ->setBottom(0.35)
            ->setLeft(0.25)
            ->setHeader(0)
            ->setFooter(0);
        $target->setShowGridlines(false);
    }

    private function copyCell(
        Worksheet $source,
        Worksheet $target,
        int $sourceColumn,
        int $sourceRow,
        int $targetColumn,
        int $targetRow,
    ): void {
        $sourceCell = $source->getCell([$sourceColumn, $sourceRow]);
        $targetCell = $target->getCell([$targetColumn, $targetRow]);
        $targetCell->setValue($sourceCell->getValue());
        $this->copyStyle($sourceCell, $target, $targetCell->getCoordinate());

        if (Date::isDateTime($sourceCell)) {
            $targetCell->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
    }

    private function copyDrawing(Worksheet $source, Worksheet $target): void
    {
        foreach ($source->getDrawingCollection() as $drawing) {
            if (! $drawing instanceof Drawing && ! $drawing instanceof MemoryDrawing) {
                continue;
            }

            $copy = clone $drawing;
            $copy->setWorksheet($target);
            $copy->setCoordinates('A1');
        }
    }

    private function copyStyle(mixed $sourceCell, Worksheet $target, string $coordinate): void
    {
        // Style supervisor thuộc workbook nguồn, vì vậy xuất ra mảng trước khi áp dụng.
        $target->getStyle($coordinate)->applyFromArray($sourceCell->getStyle()->exportArray());
    }

    private function firstNonEmptyCell(Worksheet $sheet, int $row): mixed
    {
        for ($column = 1; $column <= 50; $column++) {
            $cell = $sheet->getCell([$column, $row]);
            if ($cell->getValue() !== null && $cell->getValue() !== '') {
                return $cell;
            }
        }

        return null;
    }
}

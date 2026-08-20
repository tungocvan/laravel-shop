<?php

namespace Modules\ClientPortal\Jobs;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Models\PriceListExport;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Models\SyncedExportProfile;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class GeneratePriceListExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PX_PER_CM = 37.7952755906;

    public function __construct(public string $exportId) {}

    public function handle(): void
    {
        $export = PriceListExport::findOrFail($this->exportId);
        $profile = SyncedExportProfile::findOrFail($export->profile_id);
        $export->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $preference = app(SyncedPricingExportPreferenceService::class)
                ->forUser((int) $profile->user_id, (int) $profile->id);

            $selected = array_fill_keys($preference['selected_columns'], true);
            $columns = array_values(array_filter(
                $preference['column_order'],
                fn ($key) => isset($selected[$key], SyncedPricingExportPreferenceService::COLUMNS[$key])
            ));

            if ($columns === []) {
                throw new \RuntimeException('Cấu hình Admin chưa chọn cột xuất.');
            }

            $rows = $export->source === 'wishlist'
                ? $this->wishlistRows($export)
                : $this->syncedRows($export);

            if ($rows === []) {
                throw new \RuntimeException('Không có dữ liệu để xuất Bảng Giá.');
            }

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Bảng giá');

            $headerFooter = (array) ($preference['header_footer'] ?? []);
            $withHeader = (bool) ($headerFooter['enabled'] ?? false);
            $headerRow = $withHeader ? 9 : 1;
            $dataRow = $headerRow + 1;
            $last = Coordinate::stringFromColumnIndex(count($columns));

            foreach ($columns as $i => $key) {
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue(
                    $letter.$headerRow,
                    $preference['headers'][$key] ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['label']
                );
                $sheet->getColumnDimension($letter)
                    ->setAutoSize(false)
                    ->setWidth((float) ($preference['widths'][$key] ?? 120), 'px');
            }

            if ($withHeader) {
                $this->renderHeader($sheet, $preference, $headerFooter, $last);
            }

            $sheet->getStyle("A{$headerRow}:{$last}{$headerRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$headerRow}:{$last}{$headerRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            foreach (array_values($rows) as $ri => $row) {
                $excelRow = $dataRow + $ri;

                foreach ($columns as $ci => $key) {
                    $letter = Coordinate::stringFromColumnIndex($ci + 1);
                    $coordinate = $letter.$excelRow;
                    $value = $this->value($row, $key, $ri + 1);

                    if (is_array($value)) {
                        $value = implode('; ', array_map('strval', $value));
                    }

                    $type = (string) ($preference['data_types'][$key]
                        ?? SyncedPricingExportPreferenceService::COLUMNS[$key]['type']
                        ?? 'auto');
                    $decimals = (int) ($preference['decimals'][$key] ?? 0);

                    $this->writeTypedCell($sheet, $coordinate, $value, $type, $decimals);

                    $align = match ($preference['alignments'][$key] ?? 'left') {
                        'center' => Alignment::HORIZONTAL_CENTER,
                        'right' => Alignment::HORIZONTAL_RIGHT,
                        default => Alignment::HORIZONTAL_LEFT,
                    };

                    $sheet->getStyle($coordinate)
                        ->getAlignment()
                        ->setHorizontal($align)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                }
            }

            $end = $dataRow + count($rows) - 1;
            $sheet->getStyle("A{$headerRow}:{$last}{$end}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            if ($withHeader) {
                $this->renderFooter($sheet, $preference, $headerFooter, $last, $end);
            }

            $name = 'bang-gia-'.now()->format('Ymd-His').'.xlsx';
            $directory = 'client-portal/price-lists/'.$export->user_id;
            $path = $directory.'/'.$name;
            $tmp = tempnam(sys_get_temp_dir(), 'price-list-');

            if ($tmp === false) {
                throw new \RuntimeException('Không thể tạo file Excel tạm.');
            }

            $xlsx = $tmp.'.xlsx';
            @unlink($tmp);
            (new Xlsx($spreadsheet))->save($xlsx);

            $contents = file_get_contents($xlsx);
            if ($contents === false) {
                @unlink($xlsx);
                throw new \RuntimeException('Không thể đọc file Excel vừa tạo.');
            }

            $disk = Storage::disk('local');
            $disk->makeDirectory($directory);
            $this->normalizePermissions($disk->path($directory), 0775);
            $written = $disk->put($path, $contents);
            @unlink($xlsx);
            $spreadsheet->disconnectWorksheets();

            if (! $written || ! $disk->exists($path)) {
                throw new \RuntimeException('Không thể lưu file Excel vào storage.');
            }

            $this->normalizePermissions($disk->path($path), 0664);
            $this->normalizePermissions($disk->path($directory), 0775);

            $export->update([
                'status' => 'completed',
                'items_count' => count($rows),
                'file_path' => $path,
                'file_name' => $name,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    private function writeTypedCell($sheet, string $coordinate, mixed $value, string $type, int $decimals): void
    {
        if ($type === 'string') {
            $sheet->setCellValueExplicit(
                $coordinate,
                $value === null ? '' : (string) $value,
                DataType::TYPE_STRING
            );

            return;
        }

        if ($type === 'number') {
            if ($value === null || $value === '') {
                $sheet->setCellValue($coordinate, null);
                return;
            }

            if (is_numeric($value)) {
                $sheet->setCellValue($coordinate, (float) $value);
                $sheet->getStyle($coordinate)
                    ->getNumberFormat()
                    ->setFormatCode($this->numberFormatCode($decimals));
                return;
            }

            $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            return;
        }

        if ($type === 'date') {
            if ($value === null || $value === '') {
                $sheet->setCellValue($coordinate, null);
                return;
            }

            $date = $this->parseDate($value);
            if ($date !== null) {
                $sheet->setCellValue($coordinate, ExcelDate::PHPToExcel($date));
                $sheet->getStyle($coordinate)
                    ->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');
                return;
            }

            $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            return;
        }

        $sheet->setCellValue($coordinate, $value);
    }

    private function numberFormatCode(int $decimals): string
    {
        $decimals = max(0, min(6, $decimals));

        return $decimals > 0
            ? '#,##0.'.str_repeat('0', $decimals)
            : '#,##0';
    }

    private function parseDate(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizePermissions(string $path, int $mode): void
    {
        if (file_exists($path)) {
            @chmod($path, $mode);
        }
    }

    private function renderHeader($sheet, array $p, array $s, string $last): void
    {
        $sheet->mergeCells('A1:B5');
        foreach (range(1, 5) as $r) {
            $sheet->getRowDimension($r)->setRowHeight(22);
            if ($last !== 'C') {
                $sheet->mergeCells("C{$r}:{$last}{$r}");
            }
        }

        $sheet->setCellValue('C1', (string) ($s['company_name'] ?? ''));
        $sheet->setCellValue('C2', 'Địa chỉ: '.(string) ($s['address'] ?? ''));
        $sheet->setCellValue('C3', 'Mã số thuế: '.(string) ($s['tax_code'] ?? ''));
        $sheet->setCellValue('C4', 'Số điện thoại: '.(string) ($s['phone'] ?? ''));
        $sheet->setCellValue('C5', 'Email: '.(string) ($s['email'] ?? ''));
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);

        $sheet->mergeCells("A6:{$last}6");
        $sheet->setCellValue('A6', (string) ($s['title'] ?? 'BẢNG BÁO GIÁ'));
        $sheet->getStyle("A6:{$last}6")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A6:{$last}6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A7:{$last}7");
        $sheet->setCellValue('A7', 'Kính gửi: '.(string) ($s['recipient'] ?? ''));
        $sheet->mergeCells("A8:{$last}8");
        $sheet->setCellValue('A8', (string) ($s['intro'] ?? ''));
        $sheet->getStyle("A1:{$last}8")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        $this->drawingCenteredInRange(
            $sheet,
            $p['logo_path'] ?? null,
            'A',
            'B',
            1,
            5,
            (float) ($s['logo_width_cm'] ?? 2.48),
            (float) ($s['logo_height_cm'] ?? 3.83),
            'Logo công ty'
        );
    }

    private function renderFooter($sheet, array $p, array $s, string $last, int $end): void
    {
        $date = $end + 2;
        $title = $date + 1;
        $sig = $title + 1;
        $name = $sig + 1;

        foreach ([$date, $title, $sig, $name] as $r) {
            $sheet->mergeCells("A{$r}:{$last}{$r}");
        }

        $year = trim((string) ($s['footer_year'] ?? '')) ?: now()->format('Y');
        $loc = trim((string) ($s['footer_location'] ?? 'Tp.HCM'));

        $sheet->setCellValue("A{$date}", "{$loc}, ngày…..tháng…...năm {$year}");
        $sheet->setCellValue("A{$title}", (string) ($s['signatory_title'] ?? 'GIÁM ĐỐC CÔNG TY'));
        $sheet->setCellValue("A{$name}", (string) ($s['signatory_name'] ?? ''));
        $sheet->getStyle("A{$date}:{$last}{$name}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$title}:{$last}{$title}")->getFont()->setBold(true);
        $sheet->getStyle("A{$name}:{$last}{$name}")->getFont()->setBold(true);

        $heightCm = (float) ($s['signature_height_cm'] ?? 2.0);
        $sheet->getRowDimension($sig)->setRowHeight(max(50, $heightCm * 28.35 + 12));
        $sheet->getRowDimension($name)->setRowHeight(22);

        $this->drawingCenteredInRange(
            $sheet,
            $p['signature_path'] ?? null,
            'A',
            $last,
            $sig,
            $sig,
            (float) ($s['signature_width_cm'] ?? 4.0),
            $heightCm,
            'Chữ ký Giám đốc'
        );
    }

    private function drawingCenteredInRange(
        $sheet,
        mixed $stored,
        string $startColumn,
        string $endColumn,
        int $startRow,
        int $endRow,
        float $widthCm,
        float $heightCm,
        string $name
    ): void {
        $path = is_string($stored) && trim($stored) !== ''
            ? Storage::disk('local')->path($stored)
            : null;

        if (! $path || ! is_file($path) || ! is_readable($path)) {
            return;
        }

        $width = (int) round(max(.5, min(15, $widthCm)) * self::PX_PER_CM);
        $height = (int) round(max(.5, min(15, $heightCm)) * self::PX_PER_CM);
        [$anchorColumn, $offsetX] = $this->horizontalAnchorForCenteredDrawing(
            $sheet,
            $startColumn,
            $endColumn,
            $width
        );

        $rangeHeight = $this->rangeHeightPixels($sheet, $startRow, $endRow);
        $offsetY = max(0, (int) floor(($rangeHeight - $height) / 2));

        $drawing = new Drawing();
        $drawing->setName($name)
            ->setPath($path)
            ->setResizeProportional(false)
            ->setWidthAndHeight($width, $height)
            ->setCoordinates($anchorColumn.$startRow)
            ->setOffsetX($offsetX)
            ->setOffsetY($offsetY)
            ->setWorksheet($sheet);
    }

    private function horizontalAnchorForCenteredDrawing(
        $sheet,
        string $startColumn,
        string $endColumn,
        int $drawingWidth
    ): array {
        $startIndex = Coordinate::columnIndexFromString($startColumn);
        $endIndex = Coordinate::columnIndexFromString($endColumn);
        $columnWidths = [];
        $rangeWidth = 0;

        for ($index = $startIndex; $index <= $endIndex; $index++) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $pixels = $this->columnWidthPixels($sheet, $letter);
            $columnWidths[$letter] = $pixels;
            $rangeWidth += $pixels;
        }

        $targetX = max(0, (int) floor(($rangeWidth - $drawingWidth) / 2));
        $cursor = 0;

        foreach ($columnWidths as $letter => $pixels) {
            if ($targetX < $cursor + $pixels) {
                return [$letter, max(0, $targetX - $cursor)];
            }
            $cursor += $pixels;
        }

        return [$endColumn, 0];
    }

    private function columnWidthPixels($sheet, string $column): int
    {
        $width = (float) $sheet->getColumnDimension($column)->getWidth();
        if ($width <= 0) {
            $width = (float) $sheet->getDefaultColumnDimension()->getWidth();
        }

        return max(1, SharedDrawing::cellDimensionToPixels(
            $width,
            $sheet->getParent()->getDefaultStyle()->getFont()
        ));
    }

    private function rangeHeightPixels($sheet, int $startRow, int $endRow): int
    {
        $height = 0;
        $defaultHeight = (float) $sheet->getDefaultRowDimension()->getRowHeight();
        if ($defaultHeight <= 0) {
            $defaultHeight = 15;
        }

        for ($row = $startRow; $row <= $endRow; $row++) {
            $points = (float) $sheet->getRowDimension($row)->getRowHeight();
            if ($points <= 0) {
                $points = $defaultHeight;
            }
            $height += (int) round($points * 96 / 72);
        }

        return max(1, $height);
    }

    private function value(array $row, string $key, int $stt): mixed
    {
        if ($key === 'stt') {
            return $stt;
        }

        if ($key === 'thanh_tien') {
            return is_numeric($row['don_gia_vat'] ?? $row['don_gia'] ?? null)
                && is_numeric($row['so_luong'] ?? null)
                ? (float) ($row['don_gia_vat'] ?? $row['don_gia']) * (float) $row['so_luong']
                : null;
        }

        return $row[$key] ?? null;
    }

    private function syncedRows(PriceListExport $export): array
    {
        return PricingResult::whereIn('source_id', $export->selected_ids)
            ->get()
            ->map(fn ($model) => $model->toArray())
            ->all();
    }

    private function wishlistRows(PriceListExport $export): array
    {
        return PricingWishlist::where('user_id', $export->user_id)
            ->whereIn('id', $export->selected_ids)
            ->get()
            ->map(function ($model) {
                $snapshot = $model->snapshot ?? [];

                return array_merge($snapshot, [
                    'ten_thuoc' => $snapshot['ten_thuoc'] ?? $snapshot['tenThuoc'] ?? $model->medicine_name,
                    'ten_hoat_chat' => $snapshot['ten_hoat_chat'] ?? $snapshot['tenHoatChat'] ?? $model->active_ingredient,
                    'nong_do' => $snapshot['nong_do'] ?? $snapshot['nongDo'] ?? $model->strength,
                    'ma_tbmt' => $snapshot['ma_tbmt'] ?? $snapshot['maTbmt'] ?? $model->ma_tbmt,
                    'don_gia' => $snapshot['don_gia'] ?? $snapshot['donGia'] ?? null,
                    'winning_name' => $snapshot['winning_name'] ?? $snapshot['winningName'] ?? [],
                    'ten_co_so_san_xuat' => $snapshot['ten_co_so_san_xuat'] ?? $snapshot['tenCoSoSanXuat'] ?? null,
                    'nuoc_san_xuat' => $snapshot['nuoc_san_xuat'] ?? $snapshot['nuocSanXuat'] ?? null,
                    'quy_cach_dong_goi' => $snapshot['quy_cach_dong_goi'] ?? $snapshot['quyCachDongGoi'] ?? null,
                    'don_vi_tinh' => $snapshot['don_vi_tinh'] ?? $snapshot['donViTinh'] ?? null,
                ]);
            })
            ->all();
    }
}

<?php

namespace Modules\Muasamcong\Services;

use Modules\Muasamcong\Models\SyncedExportPreference;

class SyncedPricingExportPreferenceService
{
    public const COLUMNS = [
        'stt' => ['label' => 'STT', 'align' => 'center', 'width' => 8],
        'stt_tt20_2022' => ['label' => 'STT TT20/2022', 'align' => 'center', 'width' => 14],
        'ten_thuoc' => ['label' => 'Tên thuốc', 'align' => 'left', 'width' => 24],
        'nhom_thuoc' => ['label' => 'Nhóm thuốc', 'align' => 'center', 'width' => 12],
        'ten_hoat_chat' => ['label' => 'Hoạt chất', 'align' => 'left', 'width' => 28],
        'nong_do' => ['label' => 'Nồng độ / Hàm lượng', 'align' => 'left', 'width' => 20],
        'duong_dung' => ['label' => 'Đường dùng', 'align' => 'left', 'width' => 16],
        'dang_bao_che' => ['label' => 'Dạng bào chế', 'align' => 'left', 'width' => 18],
        'don_vi_tinh' => ['label' => 'Đơn vị tính', 'align' => 'center', 'width' => 12],
        'quy_cach_dong_goi' => ['label' => 'Quy cách đóng gói', 'align' => 'left', 'width' => 24],
        'gdklh_gpnk' => ['label' => 'GĐKLH / GPNK', 'align' => 'left', 'width' => 20],
        'han_dung' => ['label' => 'Hạn dùng', 'align' => 'left', 'width' => 14],
        'ten_co_so_san_xuat' => ['label' => 'Cơ sở sản xuất', 'align' => 'left', 'width' => 28],
        'nuoc_san_xuat' => ['label' => 'Nước sản xuất', 'align' => 'left', 'width' => 18],
        'don_gia' => ['label' => 'Giá trúng thầu', 'align' => 'right', 'width' => 14],
        'gia_kk_kkl' => ['label' => 'Giá KK / KKL', 'align' => 'right', 'width' => 14],
        'don_gia_vat' => ['label' => 'Đơn giá (VAT)', 'align' => 'right', 'width' => 14],
        'so_luong' => ['label' => 'Số lượng', 'align' => 'right', 'width' => 14],
        'thanh_tien' => ['label' => 'Thành tiền', 'align' => 'right', 'width' => 18],
        'winning_code' => ['label' => 'Mã nhà thầu trúng', 'align' => 'left', 'width' => 20],
        'winning_name' => ['label' => 'Đơn vị trúng thầu', 'align' => 'left', 'width' => 32],
        'ten_cdt_bmt' => ['label' => 'Chủ đầu tư / Bên mời thầu', 'align' => 'left', 'width' => 32],
        'ma_cdt' => ['label' => 'Mã chủ đầu tư', 'align' => 'left', 'width' => 18],
        'ma_tbmt' => ['label' => 'Mã TBMT', 'align' => 'center', 'width' => 18],
        'bid_form' => ['label' => 'Hình thức dự thầu', 'align' => 'left', 'width' => 20],
        'dia_diem' => ['label' => 'Địa điểm', 'align' => 'left', 'width' => 28],
        'so_quyet_dinh' => ['label' => 'Số quyết định', 'align' => 'left', 'width' => 20],
        'ngay_ban_hanh_quyet_dinh' => ['label' => 'Ngày quyết định', 'align' => 'center', 'width' => 16],
        'ngay_dang_tai_kqlcnt' => ['label' => 'Ngày đăng KQLCNT', 'align' => 'center', 'width' => 16],
        'so_nha_thau_tham_du' => ['label' => 'Số nhà thầu tham dự', 'align' => 'right', 'width' => 16],
        'type' => ['label' => 'Loại', 'align' => 'left', 'width' => 14],
        'tab' => ['label' => 'Tab', 'align' => 'left', 'width' => 14],
        'medicines' => ['label' => 'Medicines', 'align' => 'left', 'width' => 14],
        'synced_at' => ['label' => 'Đồng bộ lúc', 'align' => 'center', 'width' => 18],
    ];

    public function forUser(int $userId): array
    {
        $preference = SyncedExportPreference::query()->where('user_id', $userId)->first();
        $allKeys = array_keys(self::COLUMNS);

        if ($preference === null) {
            return [
                'column_order' => $allKeys,
                'selected_columns' => $allKeys,
                'alignments' => $this->defaultAlignments(),
                'widths' => $this->defaultWidths(),
            ];
        }

        return [
            'column_order' => $this->normalizeOrder((array) $preference->column_order),
            'selected_columns' => array_values(array_filter((array) $preference->selected_columns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))),
            'alignments' => $this->normalizeAlignments((array) $preference->alignments),
            'widths' => $this->normalizeWidths((array) $preference->widths),
        ];
    }

    public function save(int $userId, array $columnOrder, array $selectedColumns, array $alignments, array $widths = []): array
    {
        $order = $this->normalizeOrder($columnOrder);
        $selectedLookup = array_fill_keys(array_values(array_filter($selectedColumns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))), true);
        $selected = array_values(array_filter($order, fn (string $key): bool => isset($selectedLookup[$key])));
        $normalizedAlignments = $this->normalizeAlignments($alignments);
        $normalizedWidths = $this->normalizeWidths($widths);

        SyncedExportPreference::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'column_order' => $order,
                'selected_columns' => $selected,
                'alignments' => $normalizedAlignments,
                'widths' => $normalizedWidths,
            ]
        );

        return [
            'column_order' => $order,
            'selected_columns' => $selected,
            'alignments' => $normalizedAlignments,
            'widths' => $normalizedWidths,
        ];
    }

    private function normalizeOrder(array $order): array
    {
        $valid = collect($order)
            ->filter(fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))
            ->unique()
            ->values()
            ->all();

        foreach (array_keys(self::COLUMNS) as $key) {
            if (! in_array($key, $valid, true)) {
                $valid[] = $key;
            }
        }

        return $valid;
    }

    private function normalizeAlignments(array $alignments): array
    {
        $normalized = [];
        foreach (self::COLUMNS as $key => $column) {
            $alignment = $alignments[$key] ?? $column['align'];
            $normalized[$key] = in_array($alignment, ['left', 'center', 'right'], true) ? $alignment : $column['align'];
        }

        return $normalized;
    }

    private function normalizeWidths(array $widths): array
    {
        $normalized = [];
        foreach (self::COLUMNS as $key => $column) {
            $width = $widths[$key] ?? $column['width'];
            $width = is_numeric($width) ? (float) $width : (float) $column['width'];
            $normalized[$key] = max(5, min(80, $width));
        }

        return $normalized;
    }

    private function defaultAlignments(): array
    {
        return collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['align']])->all();
    }

    private function defaultWidths(): array
    {
        return collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['width']])->all();
    }
}

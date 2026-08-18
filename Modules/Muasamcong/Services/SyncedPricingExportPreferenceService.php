<?php

namespace Modules\Muasamcong\Services;

use Modules\Muasamcong\Models\SyncedExportPreference;

class SyncedPricingExportPreferenceService
{
    public const COLUMNS = [
        'stt' => ['label' => 'STT', 'align' => 'center', 'width' => 60, 'type' => 'number'],
        'stt_tt20_2022' => ['label' => 'STT TT20/2022', 'align' => 'center', 'width' => 110, 'type' => 'string'],
        'ten_thuoc' => ['label' => 'Tên thuốc', 'align' => 'left', 'width' => 180, 'type' => 'auto'],
        'nhom_thuoc' => ['label' => 'Nhóm thuốc', 'align' => 'center', 'width' => 90, 'type' => 'auto'],
        'ten_hoat_chat' => ['label' => 'Hoạt chất', 'align' => 'left', 'width' => 220, 'type' => 'auto'],
        'nong_do' => ['label' => 'Nồng độ / Hàm lượng', 'align' => 'left', 'width' => 160, 'type' => 'auto'],
        'duong_dung' => ['label' => 'Đường dùng', 'align' => 'left', 'width' => 120, 'type' => 'auto'],
        'dang_bao_che' => ['label' => 'Dạng bào chế', 'align' => 'left', 'width' => 140, 'type' => 'auto'],
        'don_vi_tinh' => ['label' => 'Đơn vị tính', 'align' => 'center', 'width' => 90, 'type' => 'auto'],
        'quy_cach_dong_goi' => ['label' => 'Quy cách đóng gói', 'align' => 'left', 'width' => 180, 'type' => 'auto'],
        'gdklh_gpnk' => ['label' => 'GĐKLH / GPNK', 'align' => 'left', 'width' => 160, 'type' => 'string'],
        'han_dung' => ['label' => 'Hạn dùng', 'align' => 'left', 'width' => 110, 'type' => 'auto'],
        'ten_co_so_san_xuat' => ['label' => 'Cơ sở sản xuất', 'align' => 'left', 'width' => 220, 'type' => 'auto'],
        'nuoc_san_xuat' => ['label' => 'Nước sản xuất', 'align' => 'left', 'width' => 140, 'type' => 'auto'],
        'don_gia' => ['label' => 'Giá trúng thầu', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'gia_kk_kkl' => ['label' => 'Giá KK / KKL', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'don_gia_vat' => ['label' => 'Đơn giá (VAT)', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'so_luong' => ['label' => 'Số lượng', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'thanh_tien' => ['label' => 'Thành tiền', 'align' => 'right', 'width' => 140, 'type' => 'number'],
        'winning_code' => ['label' => 'Mã nhà thầu trúng', 'align' => 'left', 'width' => 160, 'type' => 'string'],
        'winning_name' => ['label' => 'Đơn vị trúng thầu', 'align' => 'left', 'width' => 240, 'type' => 'auto'],
        'ten_cdt_bmt' => ['label' => 'Chủ đầu tư / Bên mời thầu', 'align' => 'left', 'width' => 240, 'type' => 'auto'],
        'ma_cdt' => ['label' => 'Mã chủ đầu tư', 'align' => 'left', 'width' => 140, 'type' => 'string'],
        'ma_tbmt' => ['label' => 'Mã TBMT', 'align' => 'center', 'width' => 140, 'type' => 'string'],
        'bid_form' => ['label' => 'Hình thức dự thầu', 'align' => 'left', 'width' => 160, 'type' => 'auto'],
        'dia_diem' => ['label' => 'Địa điểm', 'align' => 'left', 'width' => 220, 'type' => 'auto'],
        'so_quyet_dinh' => ['label' => 'Số quyết định', 'align' => 'left', 'width' => 160, 'type' => 'string'],
        'ngay_ban_hanh_quyet_dinh' => ['label' => 'Ngày quyết định', 'align' => 'center', 'width' => 120, 'type' => 'date'],
        'ngay_dang_tai_kqlcnt' => ['label' => 'Ngày đăng KQLCNT', 'align' => 'center', 'width' => 120, 'type' => 'date'],
        'so_nha_thau_tham_du' => ['label' => 'Số nhà thầu tham dự', 'align' => 'right', 'width' => 130, 'type' => 'number'],
        'type' => ['label' => 'Loại', 'align' => 'left', 'width' => 100, 'type' => 'auto'],
        'tab' => ['label' => 'Tab', 'align' => 'left', 'width' => 100, 'type' => 'auto'],
        'medicines' => ['label' => 'Medicines', 'align' => 'left', 'width' => 100, 'type' => 'auto'],
        'synced_at' => ['label' => 'Đồng bộ lúc', 'align' => 'center', 'width' => 140, 'type' => 'auto'],
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
                'data_types' => $this->defaultDataTypes(),
            ];
        }

        return [
            'column_order' => $this->normalizeOrder((array) $preference->column_order),
            'selected_columns' => array_values(array_filter((array) $preference->selected_columns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))),
            'alignments' => $this->normalizeAlignments((array) $preference->alignments),
            'widths' => $this->normalizeWidths((array) $preference->widths),
            'data_types' => $this->normalizeDataTypes((array) $preference->data_types),
        ];
    }

    public function save(
        int $userId,
        array $columnOrder,
        array $selectedColumns,
        array $alignments,
        array $widths = [],
        array $dataTypes = [],
    ): array {
        $order = $this->normalizeOrder($columnOrder);
        $selectedLookup = array_fill_keys(array_values(array_filter($selectedColumns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))), true);
        $selected = array_values(array_filter($order, fn (string $key): bool => isset($selectedLookup[$key])));
        $normalizedAlignments = $this->normalizeAlignments($alignments);
        $normalizedWidths = $this->normalizeWidths($widths);
        $normalizedDataTypes = $this->normalizeDataTypes($dataTypes);

        SyncedExportPreference::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'column_order' => $order,
                'selected_columns' => $selected,
                'alignments' => $normalizedAlignments,
                'widths' => $normalizedWidths,
                'data_types' => $normalizedDataTypes,
            ]
        );

        return [
            'column_order' => $order,
            'selected_columns' => $selected,
            'alignments' => $normalizedAlignments,
            'widths' => $normalizedWidths,
            'data_types' => $normalizedDataTypes,
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
            $normalized[$key] = (int) round(max(40, min(600, $width)));
        }

        return $normalized;
    }

    private function normalizeDataTypes(array $dataTypes): array
    {
        $normalized = [];
        foreach (self::COLUMNS as $key => $column) {
            $type = $dataTypes[$key] ?? $column['type'];
            $normalized[$key] = in_array($type, ['auto', 'number', 'string', 'date'], true) ? $type : $column['type'];
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

    private function defaultDataTypes(): array
    {
        return collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['type']])->all();
    }
}

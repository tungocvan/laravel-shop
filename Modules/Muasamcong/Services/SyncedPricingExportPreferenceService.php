<?php

namespace Modules\Muasamcong\Services;

use Modules\Muasamcong\Models\SyncedExportPreference;

class SyncedPricingExportPreferenceService
{
    public const COLUMNS = [
        'stt' => ['label' => 'STT', 'align' => 'center'],
        'stt_tt20_2022' => ['label' => 'STT TT20/2022', 'align' => 'center'],
        'ten_thuoc' => ['label' => 'Tên thuốc', 'align' => 'left'],
        'nhom_thuoc' => ['label' => 'Nhóm thuốc', 'align' => 'center'],
        'ten_hoat_chat' => ['label' => 'Hoạt chất', 'align' => 'left'],
        'nong_do' => ['label' => 'Nồng độ / Hàm lượng', 'align' => 'left'],
        'duong_dung' => ['label' => 'Đường dùng', 'align' => 'left'],
        'dang_bao_che' => ['label' => 'Dạng bào chế', 'align' => 'left'],
        'don_vi_tinh' => ['label' => 'Đơn vị tính', 'align' => 'center'],
        'quy_cach_dong_goi' => ['label' => 'Quy cách đóng gói', 'align' => 'left'],
        'gdklh_gpnk' => ['label' => 'GĐKLH / GPNK', 'align' => 'left'],
        'han_dung' => ['label' => 'Hạn dùng', 'align' => 'left'],
        'ten_co_so_san_xuat' => ['label' => 'Cơ sở sản xuất', 'align' => 'left'],
        'nuoc_san_xuat' => ['label' => 'Nước sản xuất', 'align' => 'left'],
        'don_gia' => ['label' => 'Giá trúng thầu', 'align' => 'right'],
        'gia_kk_kkl' => ['label' => 'Giá KK / KKL', 'align' => 'right'],
        'don_gia_vat' => ['label' => 'Đơn giá (VAT)', 'align' => 'right'],
        'so_luong' => ['label' => 'Số lượng', 'align' => 'right'],
        'thanh_tien' => ['label' => 'Thành tiền', 'align' => 'right'],
        'winning_code' => ['label' => 'Mã nhà thầu trúng', 'align' => 'left'],
        'winning_name' => ['label' => 'Đơn vị trúng thầu', 'align' => 'left'],
        'ten_cdt_bmt' => ['label' => 'Chủ đầu tư / Bên mời thầu', 'align' => 'left'],
        'ma_cdt' => ['label' => 'Mã chủ đầu tư', 'align' => 'left'],
        'ma_tbmt' => ['label' => 'Mã TBMT', 'align' => 'center'],
        'bid_form' => ['label' => 'Hình thức dự thầu', 'align' => 'left'],
        'dia_diem' => ['label' => 'Địa điểm', 'align' => 'left'],
        'so_quyet_dinh' => ['label' => 'Số quyết định', 'align' => 'left'],
        'ngay_ban_hanh_quyet_dinh' => ['label' => 'Ngày quyết định', 'align' => 'center'],
        'ngay_dang_tai_kqlcnt' => ['label' => 'Ngày đăng KQLCNT', 'align' => 'center'],
        'so_nha_thau_tham_du' => ['label' => 'Số nhà thầu tham dự', 'align' => 'right'],
        'type' => ['label' => 'Loại', 'align' => 'left'],
        'tab' => ['label' => 'Tab', 'align' => 'left'],
        'medicines' => ['label' => 'Medicines', 'align' => 'left'],
        'synced_at' => ['label' => 'Đồng bộ lúc', 'align' => 'center'],
    ];

    public function forUser(int $userId): array
    {
        $preference = SyncedExportPreference::query()->where('user_id', $userId)->first();
        $allKeys = array_keys(self::COLUMNS);

        if ($preference === null) {
            return [
                'column_order' => $allKeys,
                'selected_columns' => $allKeys,
                'alignments' => collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['align']])->all(),
            ];
        }

        $order = $this->normalizeOrder((array) $preference->column_order);
        $selected = array_values(array_filter((array) $preference->selected_columns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key])));
        $alignments = $this->normalizeAlignments((array) $preference->alignments);

        return [
            'column_order' => $order,
            'selected_columns' => $selected,
            'alignments' => $alignments,
        ];
    }

    public function save(int $userId, array $columnOrder, array $selectedColumns, array $alignments): array
    {
        $order = $this->normalizeOrder($columnOrder);
        $selectedLookup = array_fill_keys(array_values(array_filter($selectedColumns, fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))), true);
        $selected = array_values(array_filter($order, fn (string $key): bool => isset($selectedLookup[$key])));
        $normalizedAlignments = $this->normalizeAlignments($alignments);

        SyncedExportPreference::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'column_order' => $order,
                'selected_columns' => $selected,
                'alignments' => $normalizedAlignments,
            ]
        );

        return [
            'column_order' => $order,
            'selected_columns' => $selected,
            'alignments' => $normalizedAlignments,
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
}

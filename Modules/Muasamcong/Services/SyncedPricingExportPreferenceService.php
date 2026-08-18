<?php

namespace Modules\Muasamcong\Services;

use Modules\Muasamcong\Models\SyncedExportProfile;

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

    public const DEFAULT_HEADER_FOOTER = [
        'enabled' => false,
        'company_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
        'address' => '',
        'tax_code' => '',
        'phone' => '',
        'title' => 'BẢNG BÁO GIÁ',
        'recipient' => 'QUÝ KHÁCH HÀNG',
        'intro' => 'Công ty INAFO Việt Nam xin trân trọng gửi đến Quý Khách hàng báo giá một số sản phẩm chúng tôi đang phân phối trên thị trường hiện nay như sau:',
        'footer_location' => 'Tp.HCM',
        'signatory_title' => 'GIÁM ĐỐC CÔNG TY',
        'footer_year' => '',
    ];

    public function profilesForUser(int $userId): array
    {
        return SyncedExportProfile::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default'])
            ->map(fn (SyncedExportProfile $profile): array => [
                'id' => (int) $profile->id,
                'name' => (string) $profile->name,
                'is_default' => (bool) $profile->is_default,
            ])
            ->all();
    }

    public function forUser(int $userId, ?int $profileId = null): array
    {
        $profile = SyncedExportProfile::query()
            ->where('user_id', $userId)
            ->when($profileId !== null && $profileId > 0, fn ($query) => $query->whereKey($profileId))
            ->when($profileId === null || $profileId <= 0, fn ($query) => $query->orderByDesc('is_default')->orderBy('id'))
            ->first();

        return $profile === null ? $this->defaults() : $this->profilePayload($profile);
    }

    public function saveProfile(
        int $userId,
        string $name,
        array $columnOrder,
        array $selectedColumns,
        array $headers,
        array $alignments,
        array $widths = [],
        array $dataTypes = [],
        array $decimals = [],
        array $headerFooter = [],
        ?string $logoPath = null,
        ?string $signaturePath = null,
        ?int $profileId = null,
        bool $makeDefault = false,
    ): array {
        $name = trim($name);
        $name = $name !== '' ? mb_substr($name, 0, 120) : 'Cấu hình Excel';
        $order = $this->normalizeOrder($columnOrder);
        $selectedLookup = array_fill_keys(array_values(array_filter(
            $selectedColumns,
            fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key])
        )), true);
        $selected = array_values(array_filter($order, fn (string $key): bool => isset($selectedLookup[$key])));

        $profile = $profileId !== null && $profileId > 0
            ? SyncedExportProfile::query()->where('user_id', $userId)->findOrFail($profileId)
            : new SyncedExportProfile(['user_id' => $userId]);

        if (! $profile->exists && ! SyncedExportProfile::query()->where('user_id', $userId)->exists()) {
            $makeDefault = true;
        }

        if ($makeDefault) {
            SyncedExportProfile::query()->where('user_id', $userId)->update(['is_default' => false]);
        }

        $profile->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'is_default' => $makeDefault || (bool) $profile->is_default,
            'column_order' => $order,
            'selected_columns' => $selected,
            'headers' => $this->normalizeHeaders($headers),
            'alignments' => $this->normalizeAlignments($alignments),
            'widths' => $this->normalizeWidths($widths),
            'data_types' => $this->normalizeDataTypes($dataTypes),
            'decimals' => $this->normalizeDecimals($decimals),
            'header_footer' => $this->normalizeHeaderFooter($headerFooter),
            'logo_path' => $logoPath,
            'signature_path' => $signaturePath,
        ])->save();

        return $this->profilePayload($profile->fresh());
    }

    public function duplicateProfile(int $userId, int $profileId): array
    {
        $source = SyncedExportProfile::query()->where('user_id', $userId)->findOrFail($profileId);
        $copy = $source->replicate();
        $copy->name = $this->uniqueCopyName($userId, (string) $source->name);
        $copy->is_default = false;
        $copy->save();

        return $this->profilePayload($copy);
    }

    public function deleteProfile(int $userId, int $profileId): void
    {
        $profile = SyncedExportProfile::query()->where('user_id', $userId)->findOrFail($profileId);
        $wasDefault = (bool) $profile->is_default;
        $profile->delete();

        if ($wasDefault) {
            SyncedExportProfile::query()->where('user_id', $userId)->orderBy('id')->first()?->update(['is_default' => true]);
        }
    }

    public function setDefault(int $userId, int $profileId): array
    {
        $profile = SyncedExportProfile::query()->where('user_id', $userId)->findOrFail($profileId);
        SyncedExportProfile::query()->where('user_id', $userId)->update(['is_default' => false]);
        $profile->update(['is_default' => true]);

        return $this->profilePayload($profile->fresh());
    }

    private function profilePayload(SyncedExportProfile $profile): array
    {
        return [
            'profile_id' => (int) $profile->id,
            'profile_name' => (string) $profile->name,
            'is_default' => (bool) $profile->is_default,
            'column_order' => $this->normalizeOrder((array) $profile->column_order),
            'selected_columns' => array_values(array_filter(
                (array) $profile->selected_columns,
                fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key])
            )),
            'headers' => $this->normalizeHeaders((array) $profile->headers),
            'alignments' => $this->normalizeAlignments((array) $profile->alignments),
            'widths' => $this->normalizeWidths((array) $profile->widths),
            'data_types' => $this->normalizeDataTypes((array) $profile->data_types),
            'decimals' => $this->normalizeDecimals((array) $profile->decimals),
            'header_footer' => $this->normalizeHeaderFooter((array) $profile->header_footer),
            'logo_path' => $profile->logo_path,
            'signature_path' => $profile->signature_path,
        ];
    }

    private function defaults(): array
    {
        $allKeys = array_keys(self::COLUMNS);

        return [
            'profile_id' => null,
            'profile_name' => 'Mặc định',
            'is_default' => false,
            'column_order' => $allKeys,
            'selected_columns' => $allKeys,
            'headers' => $this->defaultHeaders(),
            'alignments' => $this->defaultAlignments(),
            'widths' => $this->defaultWidths(),
            'data_types' => $this->defaultDataTypes(),
            'decimals' => $this->defaultDecimals(),
            'header_footer' => self::DEFAULT_HEADER_FOOTER,
            'logo_path' => null,
            'signature_path' => null,
        ];
    }

    private function normalizeOrder(array $order): array
    {
        $valid = collect($order)->filter(fn (mixed $key): bool => is_string($key) && isset(self::COLUMNS[$key]))->unique()->values()->all();
        foreach (array_keys(self::COLUMNS) as $key) {
            if (! in_array($key, $valid, true)) {
                $valid[] = $key;
            }
        }

        return $valid;
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach (self::COLUMNS as $key => $column) {
            $header = trim((string) ($headers[$key] ?? $column['label']));
            $normalized[$key] = $header !== '' ? mb_substr($header, 0, 120) : $column['label'];
        }

        return $normalized;
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

    private function normalizeDecimals(array $decimals): array
    {
        $normalized = [];
        foreach (self::COLUMNS as $key => $column) {
            $value = $decimals[$key] ?? 0;
            $normalized[$key] = max(0, min(6, is_numeric($value) ? (int) $value : 0));
        }

        return $normalized;
    }

    private function normalizeHeaderFooter(array $settings): array
    {
        $normalized = self::DEFAULT_HEADER_FOOTER;
        $normalized['enabled'] = (bool) ($settings['enabled'] ?? $normalized['enabled']);

        foreach (['company_name', 'address', 'tax_code', 'phone', 'title', 'recipient', 'intro', 'footer_location', 'signatory_title', 'footer_year'] as $key) {
            $value = trim((string) ($settings[$key] ?? $normalized[$key]));
            $normalized[$key] = mb_substr($value, 0, $key === 'intro' ? 2000 : 255);
        }

        return $normalized;
    }

    private function uniqueCopyName(int $userId, string $name): string
    {
        $base = mb_substr(trim($name).' - Bản sao', 0, 110);
        $candidate = $base;
        $suffix = 2;

        while (SyncedExportProfile::query()->where('user_id', $userId)->where('name', $candidate)->exists()) {
            $candidate = mb_substr($base, 0, 110)." {$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function defaultHeaders(): array
    {
        return collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['label']])->all();
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

    private function defaultDecimals(): array
    {
        return collect(self::COLUMNS)->mapWithKeys(fn (array $column, string $key): array => [$key => 0])->all();
    }
}

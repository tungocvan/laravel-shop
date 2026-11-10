<?php

namespace Modules\Pharma\Services;

use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\MedicineExcelProfile;

class MedicineExcelProfileService
{
    public const BASE_COLUMNS = [
        'stt' => 'STT',
        'medicine_code' => 'Mã thuốc',
        'name' => 'Tên thuốc',
        'product_type' => 'Loại sản phẩm',
        'sku' => 'SKU',
        'registration_number' => 'GPLH',
        'circular_group' => 'Nhóm thuốc',
        'circular_order_number' => 'STT thông tư',
        'visa_validity_date' => 'Hiệu lực Visa',
        'gmp_certification_date' => 'GMP cơ sở sản xuất',
        'is_special_control' => 'Thuốc kiểm soát đặc biệt (KSĐB)',
        'active_ingredients' => 'Hoạt chất',
        'concentration' => 'Hàm lượng',
        'dosage_form' => 'Dạng bào chế',
        'route_of_administration' => 'Đường dùng',
        'unit' => 'Đơn vị tính',
        'packaging_specification' => 'Quy cách',
        'declared_price' => 'Giá kê khai',
        'selling_price' => 'Giá bán',
        'registered_company' => 'Cơ sở đăng ký',
        'manufacturing_company' => 'Cơ sở sản xuất',
        'manufacturing_country' => 'Nước sản xuất',
        'profile_status' => 'Chất lượng master',
        'shelf_life' => 'Hạn dùng sản phẩm',
    ];

    public const COLUMNS = self::BASE_COLUMNS + [
        'cost_price' => 'Giá vốn',
        'supplier_name' => 'Tên nhà cung cấp',
        'hssp_status' => 'Trạng thái HSSP',
        'winning_company_name' => 'Công ty trúng thầu',
        'investor_name' => 'Nơi trúng thầu',
        'decision_number' => 'Số quyết định',
        'decision_date' => 'Ngày quyết định',
        'contract_period_text' => 'Thời gian trúng thầu',
        'winning_price' => 'Giá trúng thầu',
        'winning_quantity' => 'Số lượng trúng thầu',
        'allocated_quantity' => 'Số lượng phân bổ',
    ];

    public function defaults(): array
    {
        $columns = array_values(array_diff(array_keys(self::BASE_COLUMNS), [
            'circular_order_number', 'visa_validity_date', 'gmp_certification_date', 'is_special_control',
        ]));
        return [
            'id' => null, 'name' => 'Mặc định', 'is_default' => true,
            'columns' => $columns,
            'headers' => self::COLUMNS,
            'widths' => array_fill_keys(array_keys(self::COLUMNS), 130),
            'alignments' => array_fill_keys(array_keys(self::COLUMNS), 'left'),
            'settings' => [
                'title' => 'DANH MỤC THUỐC CHUẨN',
                'company_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
                'paper_size' => 'A4', 'orientation' => 'landscape',
                'header_enabled' => true,
                'font_family' => 'Times New Roman',
                'header_font_size' => 12,
                'body_font_size' => 11,
                'header_fill' => 'EFF4FA',
                'body_border' => true,
                'fit_to_page' => false,
                'report_header' => ['enabled' => false, 'show_logo' => false, 'logo_data' => null, 'logo_width' => 110, 'company_name' => '', 'address' => '', 'office' => '', 'tax_code' => '', 'phone' => '', 'email' => '', 'recipient' => '', 'intro' => ''],
                'report_footer' => ['enabled' => false, 'date_text' => '', 'signer_title' => '', 'signer_name' => '', 'signature_rows' => 4],
                'margin_top_cm' => 0.8, 'margin_bottom_cm' => 0.8,
                'margin_left_cm' => 0.8, 'margin_right_cm' => 0.8,
                'margin_header_cm' => 0.8, 'margin_footer_cm' => 0.8,
                'center_horizontally' => true, 'center_vertically' => false,
            ],
        ];
    }

    public function listForUser(int $userId, string $guard = 'admin'): array
    {
        return MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)
            ->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default'])->toArray();
    }

    public function forUser(int $userId, ?int $id = null, string $guard = 'admin'): array
    {
        $query = MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId);
        $profile = $id ? $query->findOrFail($id) : $query->orderByDesc('is_default')->orderBy('id')->first();
        return $profile ? array_merge($profile->only(['id', 'name', 'is_default', 'columns', 'headers', 'widths', 'alignments', 'settings'])) : $this->defaults();
    }

    public function save(int $userId, array $data, ?int $id = null, string $guard = 'admin'): array
    {
        $defaults = $this->defaults();
        $columns = array_values(array_unique(array_filter((array) ($data['columns'] ?? []),
            fn ($key) => is_string($key) && isset(self::COLUMNS[$key]))));
        if ($columns === []) {
            throw ValidationException::withMessages(['columns' => 'Chọn ít nhất một cột xuất Excel.']);
        }
        $profile = $id ? MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->findOrFail($id) : new MedicineExcelProfile();
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 120);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Nhập tên cấu hình.']);
        }
        $exists = MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->where('name', $name)
            ->when($profile->exists, fn ($query) => $query->whereKeyNot($profile->id))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'Tên cấu hình đã tồn tại.']);
        }
        $makeDefault = (bool) ($data['is_default'] ?? false)
            || ! MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->exists();
        if ($makeDefault) {
            MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->update(['is_default' => false]);
        }
        $headers = []; $widths = []; $alignments = [];
        foreach (self::COLUMNS as $key => $label) {
            $headers[$key] = mb_substr(trim((string) ($data['headers'][$key] ?? $label)) ?: $label, 0, 120);
            $widths[$key] = max(40, min(400, (int) ($data['widths'][$key] ?? 130)));
            $alignment = $data['alignments'][$key] ?? 'left';
            $alignments[$key] = in_array($alignment, ['left', 'center', 'right'], true)
                ? $alignment : 'left';
        }
        $settings = array_replace($defaults['settings'], (array) ($data['settings'] ?? []));
        $settings['paper_size'] = in_array($settings['paper_size'], ['A4', 'A3', 'LETTER'], true) ? $settings['paper_size'] : 'A4';
        $settings['orientation'] = in_array($settings['orientation'], ['portrait', 'landscape'], true) ? $settings['orientation'] : 'landscape';
        $settings['title'] = mb_substr(trim((string) $settings['title']), 0, 200);
        $settings['company_name'] = mb_substr(trim((string) $settings['company_name']), 0, 200);
        $settings['header_enabled'] = (bool) $settings['header_enabled'];
        $settings['font_family'] = in_array($settings['font_family'], ['Times New Roman', 'Arial', 'Calibri'], true) ? $settings['font_family'] : 'Times New Roman';
        $settings['header_font_size'] = max(8, min(20, (int) $settings['header_font_size']));
        $settings['body_font_size'] = max(8, min(20, (int) $settings['body_font_size']));
        $settings['header_fill'] = in_array($settings['header_fill'], ['EFF4FA', 'F1F5F9', 'FFFFFF', 'EDE9FE'], true) ? $settings['header_fill'] : 'EFF4FA';
        $settings['body_border'] = (bool) $settings['body_border'];
        $settings['fit_to_page'] = (bool) ($settings['fit_to_page'] ?? false);
        foreach (['top', 'bottom', 'left', 'right', 'header', 'footer'] as $side) {
            $key = 'margin_'.$side.'_cm';
            $settings[$key] = max(0, min(5, (float) ($settings[$key] ?? 0.8)));
        }
        $settings['center_horizontally'] = (bool) ($settings['center_horizontally'] ?? true);
        $settings['center_vertically'] = (bool) ($settings['center_vertically'] ?? false);
        foreach (['report_header', 'report_footer'] as $section) {
            $input = is_array($settings[$section] ?? null) ? $settings[$section] : [];
            $settings[$section] = array_replace($defaults['settings'][$section], array_intersect_key($input, $defaults['settings'][$section]));
            $settings[$section]['enabled'] = (bool) $settings[$section]['enabled'];
        }
        $header = &$settings['report_header'];
        foreach (['company_name', 'address', 'office', 'tax_code', 'phone', 'email', 'recipient', 'intro'] as $key) {
            $header[$key] = mb_substr(trim((string) $header[$key]), 0, $key === 'intro' ? 1000 : 250);
        }
        $header['show_logo'] = (bool) $header['show_logo'];
        $header['logo_width'] = max(40, min(220, (int) $header['logo_width']));
        $logo = $header['logo_data'];
        if ($logo !== null && $logo !== '') {
            if (! is_string($logo) || strlen($logo) > 400000 || ! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#D', $logo, $matches)) {
                throw ValidationException::withMessages(['settings.report_header.logo_data' => 'Logo phải là PNG/JPEG hợp lệ, tối đa 256 KB.']);
            }
            $binary = base64_decode($matches[2], true);
            $imageInfo = $binary === false ? false : @getimagesizefromstring($binary);
            if ($binary === false || strlen($binary) > 262144 || ! $imageInfo || ! in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || $imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
                throw ValidationException::withMessages(['settings.report_header.logo_data' => 'Logo không hợp lệ hoặc vượt giới hạn.']);
            }
        } else {
            $header['logo_data'] = null;
        }
        unset($header);
        $footer = &$settings['report_footer'];
        foreach (['date_text', 'signer_title', 'signer_name'] as $key) {
            $footer[$key] = mb_substr(trim((string) $footer[$key]), 0, 250);
        }
        $footer['signature_rows'] = max(1, min(10, (int) $footer['signature_rows']));
        unset($footer);
        $profile->forceFill([
            'owner_guard' => $guard, 'user_id' => $userId, 'name' => $name, 'is_default' => $makeDefault || (bool) $profile->is_default,
            'columns' => $columns, 'headers' => $headers, 'widths' => $widths,
            'alignments' => $alignments, 'settings' => $settings,
        ])->save();
        return $this->forUser($userId, (int) $profile->id, $guard);
    }

    public function delete(int $userId, int $id, string $guard = 'admin'): void
    {
        $profile = MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->findOrFail($id);
        $wasDefault = $profile->is_default;
        $profile->delete();
        if ($wasDefault) {
            MedicineExcelProfile::query()->where('owner_guard', $guard)->where('user_id', $userId)->orderBy('id')->first()?->update(['is_default' => true]);
        }
    }
}

<?php

namespace Modules\Pharma\Services;

class MedicineCatalogImportMapper
{
    public function __construct(private ?MedicineCatalogNormalizer $normalizer = null)
    {
        $this->normalizer ??= new MedicineCatalogNormalizer;
    }

    public function map(array $row): array
    {
        $value = fn (array $keys) => $this->firstValue($row, $keys);

        $registrationRaw = $value(['Giấy phép lưu hành sản phẩm', 'registration_number', 'registration_number_raw']);
        $name = $value(['Tên biệt dược', 'Tên thuốc', 'Tên sản phẩm', 'name', 'brand_name']);

        return [
            'circular_order_number' => $this->clean($value(['STT TT20/2022', 'Số thứ tự theo thông tư', 'circular_order_number'])),
            'circular_group' => $this->clean($value(['Nhóm thuốc', 'Phân nhóm theo thông tư', 'circular_group'])),
            'active_ingredients' => $this->clean($value(['Tên hoạt chất', 'active_ingredients'])),
            'concentration' => $this->clean($value(['Nồng độ - Hàm lượng', 'concentration', 'strength_text'])),
            'name' => $this->clean($name),
            'dosage_form' => $this->clean($value(['Dạng bào chế', 'dosage_form'])),
            'route_of_administration' => $this->clean($value(['Đường dùng', 'route_of_administration'])),
            'unit' => $this->clean($value(['Đơn vị tính', 'unit', 'base_unit'])),
            'packaging_specification' => $this->clean($value(['Quy cách đóng gói', 'packaging_specification', 'packaging_text'])),
            'registration_number_raw' => $this->clean($registrationRaw),
            'registration_number_primary' => $this->normalizer->registrationPrimary($this->clean($registrationRaw)),
            'registration_number' => $this->normalizer->registrationPrimary($this->clean($registrationRaw)),
            'shelf_life' => $this->clean($value(['Hạn dùng', 'shelf_life'])),
            'manufacturing_company' => $this->clean($value(['Cơ sở sản xuất', 'manufacturing_company', 'manufacturer'])),
            'manufacturing_country' => $this->clean($value(['Nước sản xuất', 'manufacturing_country'])),
            'declared_price' => $this->number($value(['Giá KK/KL', 'Giá kê khai', 'declared_price'])),
        ];
    }

    public function payloadHash(array $normalized): string
    {
        ksort($normalized);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function firstValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value === '' ? null : $value;
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $raw = preg_replace('/[^0-9,.-]/u', '', (string) $value) ?? '';
        if ($raw === '') {
            return null;
        }

        if (str_contains($raw, ',') && ! str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace(',', '', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }
}

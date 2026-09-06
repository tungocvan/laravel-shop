<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

class OfficialFacilityValidator
{
    public function validate(array $row): array
    {
        $errors = [];
        if (blank($row['facility_name'] ?? null)) $errors[] = 'Tên cơ sở y tế là bắt buộc.';
        if (blank($row['province_code'] ?? null)) $errors[] = 'Mã tỉnh canonical là bắt buộc; không được suy đoán từ tên, địa chỉ hoặc mã cơ sở.';
        if (! blank($row['email'] ?? null) && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        return $errors;
    }
}

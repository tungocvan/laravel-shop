<?php

namespace Modules\Admission\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Admission\Models\SchoolSetting;

class SchoolSettingService
{
    private const CACHE_KEY = 'admission.school-settings';

    public const DEFAULTS = [
        'principal' => 'Hoàng Thụy Bích Thủy',
        'school_year' => '2026-2027',
        'school_name' => 'TRƯỜNG TIỂU HỌC NGUYỄN VĂN HƯỞNG',
        'school_managing_agency' => 'ỦY BAN NHÂN DÂN PHƯỜNG PHÚ THUẬN',
        'school_login_description' => 'Hệ thống quản trị & đăng nhập giáo viên / quản lý',
        'registration_classes' => [
            'Lớp thường',
            'Tăng cường Tiếng Anh',
            'Tích hợp',
            'Tăng cường TA + Toán và Khoa học',
        ],
        'school_campuses' => [
            ['name' => 'Cơ sở chính', 'address' => '203 Lâm Văn Bền, phường Tân Thuận, Thành phố Hồ Chí Minh'],
            ['name' => 'Phân hiệu', 'address' => '215 Trần Xuân Soạn, phường Tân Thuận, Thành phố Hồ Chí Minh'],
            ['name' => 'Điểm trường', 'address' => '37/2 Huỳnh Tấn Phát, phường Tân Thuận, Thành phố Hồ Chí Minh'],
        ],
    ];

    public function all(): array
    {
        if (! Schema::hasTable((new SchoolSetting)->getTable())) {
            return self::DEFAULTS;
        }

        $stored = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => SchoolSetting::query()->pluck('value', 'key')->all(),
        );

        $settings = array_replace(self::DEFAULTS, $stored);
        $classes = is_array($settings['registration_classes'])
            ? $settings['registration_classes']
            : json_decode((string) $settings['registration_classes'], true);
        $settings['registration_classes'] = is_array($classes)
            ? array_values(array_filter($classes, fn (mixed $class): bool => is_string($class) && trim($class) !== ''))
            : self::DEFAULTS['registration_classes'];

        $campuses = is_array($settings['school_campuses'])
            ? $settings['school_campuses']
            : json_decode((string) $settings['school_campuses'], true);
        $settings['school_campuses'] = is_array($campuses)
            ? array_values(array_filter($campuses, static fn (mixed $campus): bool =>
                is_array($campus)
                && trim((string) ($campus['name'] ?? '')) !== ''
                && trim((string) ($campus['address'] ?? '')) !== ''
            ))
            : self::DEFAULTS['school_campuses'];

        return $settings;
    }

    public function get(string $key): string
    {
        $value = $this->all()[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    public function registrationClasses(): array
    {
        return $this->all()['registration_classes'];
    }

    public function schoolCampuses(): array
    {
        return $this->all()['school_campuses'];
    }

    public function save(array $settings): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            SchoolSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->serializeValue($settings[$key] ?? self::DEFAULTS[$key])],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    private function serializeValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return trim((string) $value);
    }
}

<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use Tests\TestCase;

class SyncedPricingExportPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_multiple_named_profiles_with_custom_headers(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $first = $service->saveProfile(
            99,
            'Báo giá bệnh viện',
            ['ten_thuoc', 'gdklh_gpnk', 'nhom_thuoc'],
            ['gdklh_gpnk', 'ten_thuoc'],
            ['ten_thuoc' => 'Tên biệt dược', 'gdklh_gpnk' => 'Số GĐKLH'],
            ['ten_thuoc' => 'center', 'gdklh_gpnk' => 'right'],
            ['ten_thuoc' => 220, 'gdklh_gpnk' => 180],
            ['ten_thuoc' => 'auto', 'gdklh_gpnk' => 'string'],
        );

        $second = $service->saveProfile(
            99,
            'Danh mục nội bộ',
            ['nhom_thuoc', 'ten_thuoc'],
            ['nhom_thuoc', 'ten_thuoc'],
            ['nhom_thuoc' => 'Nhóm', 'ten_thuoc' => 'Thuốc'],
            [],
            [],
            [],
        );

        $this->assertNotSame($first['profile_id'], $second['profile_id']);
        $this->assertSame('Tên biệt dược', $first['headers']['ten_thuoc']);
        $this->assertSame(220, $first['widths']['ten_thuoc']);
        $this->assertSame('string', $first['data_types']['gdklh_gpnk']);

        $profiles = $service->profilesForUser(99);
        $this->assertCount(2, $profiles);

        $loaded = $service->forUser(99, $first['profile_id']);
        $this->assertSame('Báo giá bệnh viện', $loaded['profile_name']);
        $this->assertSame('Tên biệt dược', $loaded['headers']['ten_thuoc']);
    }

    public function test_pixel_widths_are_clamped_to_supported_bounds(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $saved = $service->saveProfile(
            100,
            'Kiểm tra width',
            ['stt', 'ten_thuoc'],
            ['stt', 'ten_thuoc'],
            [],
            [],
            ['stt' => 20, 'ten_thuoc' => 900],
            [],
        );

        $this->assertSame(40, $saved['widths']['stt']);
        $this->assertSame(600, $saved['widths']['ten_thuoc']);
    }
}

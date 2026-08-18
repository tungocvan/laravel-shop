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
            ['ten_thuoc' => 0, 'gdklh_gpnk' => 0],
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
            [],
        );

        $this->assertNotSame($first['profile_id'], $second['profile_id']);
        $this->assertSame('Tên biệt dược', $first['headers']['ten_thuoc']);
        $this->assertSame(220, $first['widths']['ten_thuoc']);
        $this->assertSame('string', $first['data_types']['gdklh_gpnk']);
        $this->assertSame(0, $first['decimals']['ten_thuoc']);

        $profiles = $service->profilesForUser(99);
        $this->assertCount(2, $profiles);

        $loaded = $service->forUser(99, $first['profile_id']);
        $this->assertSame('Báo giá bệnh viện', $loaded['profile_name']);
        $this->assertSame('Tên biệt dược', $loaded['headers']['ten_thuoc']);
    }

    public function test_it_duplicates_all_profile_settings_without_copying_default_status(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $source = $service->saveProfile(
            101,
            'Báo giá chuẩn',
            ['don_gia', 'ten_thuoc'],
            ['don_gia', 'ten_thuoc'],
            ['don_gia' => 'Đơn giá', 'ten_thuoc' => 'Tên hàng'],
            ['don_gia' => 'right', 'ten_thuoc' => 'left'],
            ['don_gia' => 120, 'ten_thuoc' => 220],
            ['don_gia' => 'number', 'ten_thuoc' => 'string'],
            ['don_gia' => 2, 'ten_thuoc' => 0],
            null,
            true,
            [
                'enabled' => true,
                'company_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
                'address' => 'TP.HCM',
                'tax_code' => '0314492345',
                'phone' => '0900000000',
                'title' => 'BẢNG BÁO GIÁ',
                'recipient' => 'QUÝ KHÁCH HÀNG',
                'intro' => 'Nội dung giới thiệu',
                'footer_location' => 'Tp.HCM',
                'signatory_title' => 'GIÁM ĐỐC CÔNG TY',
                'footer_year' => '2026',
            ],
            'muasamcong/export-profiles/101/logo.png',
            'muasamcong/export-profiles/101/signature.png',
        );

        $copy = $service->duplicateProfile(101, $source['profile_id']);

        $this->assertNotSame($source['profile_id'], $copy['profile_id']);
        $this->assertSame('Báo giá chuẩn - Bản sao', $copy['profile_name']);
        $this->assertFalse($copy['is_default']);
        $this->assertSame($source['selected_columns'], $copy['selected_columns']);
        $this->assertSame($source['headers'], $copy['headers']);
        $this->assertSame($source['alignments'], $copy['alignments']);
        $this->assertSame($source['widths'], $copy['widths']);
        $this->assertSame($source['data_types'], $copy['data_types']);
        $this->assertSame(2, $copy['decimals']['don_gia']);
        $this->assertTrue($copy['header_footer']['enabled']);
        $this->assertSame('0314492345', $copy['header_footer']['tax_code']);
        $this->assertSame($source['logo_path'], $copy['logo_path']);
        $this->assertSame($source['signature_path'], $copy['signature_path']);
    }

    public function test_number_decimals_are_clamped_to_zero_through_six(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $saved = $service->saveProfile(
            102,
            'Decimal',
            ['don_gia', 'so_luong'],
            ['don_gia', 'so_luong'],
            [],
            [],
            [],
            ['don_gia' => 'number', 'so_luong' => 'number'],
            ['don_gia' => -3, 'so_luong' => 9],
        );

        $this->assertSame(0, $saved['decimals']['don_gia']);
        $this->assertSame(6, $saved['decimals']['so_luong']);
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
            [],
        );

        $this->assertSame(40, $saved['widths']['stt']);
        $this->assertSame(600, $saved['widths']['ten_thuoc']);
    }
}

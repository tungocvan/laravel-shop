<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnterpriseManagementReportPreferencesTest extends TestCase
{
    public function test_preferences_assets_are_loaded_after_business_script(): void
    {
        $response = $this->get('/demo/bao-cao-quan-tri-doanh-nghiep');
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('js/preferences.js', $html);
        $this->assertLessThan(
            strpos($html, 'js/preferences.js'),
            strpos($html, 'js/app.js')
        );
    }

    public function test_sidebar_editor_is_browser_local_and_has_four_themes(): void
    {
        $path = public_path('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/preferences.js');
        $this->assertFileExists($path);
        $script = file_get_contents($path);
        foreach (['ruby', 'ocean', 'emerald', 'indigo', 'sidebar_v1', 'theme_v1', 'Footer Sidebar', 'Cập nhật giao diện'] as $token) {
            $this->assertStringContainsString($token, $script);
        }
        $this->assertStringContainsString('https:', $script);
        $this->assertStringNotContainsString('innerHTML = location', $script);
    }
    public function test_blade_save_does_not_export_standalone_html(): void
    {
        $script = file_get_contents(public_path('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/app.js'));
        $this->assertStringContainsString('if(ONLINE){', $script);
        $this->assertStringContainsString('Không lưu lên server', $script);
        $this->assertStringContainsString('Lưu trong trình duyệt', $script);
        $this->assertStringNotContainsString('<h4>Lưu đè dữ liệu vào file</h4>', $script);
    }
    public function test_sidebar_editor_has_shared_api_json_dragdrop_and_operator_modal(): void
    {
        $this->get('/demo/bao-cao-quan-tri-doanh-nghiep')->assertOk()
            ->assertSee('name="csrf-token"', false);

        $script = file_get_contents(public_path('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/preferences.js'));
        foreach ([
            '/demo/bao-cao-quan-tri-doanh-nghiep/menu',
            'application/x-enterprise-menu',
            'Nhập JSON',
            'Xuất JSON',
            'Lưu lên server',
            'Ghi đè preset',
            'Xóa preset',
            'X-Demo-Backup-Code',
            'type=\'password\'',
        ] as $token) {
            $this->assertStringContainsString($token, $script);
        }
    }

}

<?php

namespace Tests\Feature\Request\Architecture;

use Illuminate\Http\Request;
use Modules\Request\Http\Middleware\UseVietnameseRequestLocale;
use Tests\TestCase;

class RequestVietnameseUiContractTest extends TestCase
{
    public function test_request_web_locale_is_forced_to_vietnamese(): void
    {
        app()->setLocale('en');

        (new UseVietnameseRequestLocale)->handle(
            Request::create('/admin/requests/catalog', 'GET'),
            fn () => response('ok')
        );

        $this->assertSame('vi', app()->getLocale());
        $this->assertSame('Danh mục đề nghị', __('Request::request.catalog.title'));
        $this->assertSame('Tạo bản nháp', __('Request::request.create_draft'));
        $this->assertSame('Báo cáo Đề nghị', __('Request::request.reports.title'));
        $this->assertSame('Sổ đăng ký đề nghị', __('Request::request.reports.register'));
        $this->assertSame('Gói định nghĩa Đề nghị', __('Request::definition_package.title'));
        $this->assertSame('Chạy thử và xem khác biệt', __('Request::definition_package.preview'));
    }

    public function test_request_acceptance_ui_has_no_known_english_copy_regressions(): void
    {
        $detail = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/request-detail.blade.php'));
        $offline = file_get_contents(base_path('Modules/Request/resources/views/partials/offline-runtime.blade.php'));
        $designer = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/type-designer.blade.php'));
        $reports = file_get_contents(base_path('Modules/Request/resources/views/admin/reports.blade.php'));
        $package = file_get_contents(base_path('Modules/Request/resources/views/admin/definition-package.blade.php'));
        $seeder = file_get_contents(base_path('Modules/Request/Database/Seeders/RequestDemoSeeder.php'));

        foreach ([
            'Local draft',
            'No local draft loaded.',
            'Review and restore local values',
            'Request local safety',
            'Remove local Request data',
            'Request type designer',
            'Form builder',
            'Approval stages',
            'Equipment Request',
            'Published demo version for catalog/create testing.',
            'Use this type only for UI acceptance tests.',
            'Request reports',
            'Request register',
            'Export foundation ready',
            'Definition package',
            'Dry-run and review diff',
            'Create draft from reviewed package',
        ] as $englishCopy) {
            $this->assertStringNotContainsString($englishCopy, $detail.$offline.$designer.$reports.$package.$seeder);
        }
    }
}

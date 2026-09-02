<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestDefinitionPackageWorkspaceContractTest extends TestCase
{
    public function test_definition_package_is_preview_first_traceable_and_import_safe(): void
    {
        $controller = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestDefinitionPackageController.php'));
        $dryRun = file_get_contents(base_path('Modules/Request/Application/Services/DryRunRequestDefinitionPackage.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/admin/definition-package.blade.php'));

        foreach (['Gói định nghĩa', 'Xuất bản phát hành hiện hành', 'Nhập gói định nghĩa', 'Kiểm tra trước khi nhập', 'Phạm vi thay đổi', 'Ánh xạ bắt buộc', 'Xác nhận tạo bản nháp mới'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        foreach (['Phiên bản hiện hành', 'Bản nháp hiện tại', 'Checksum gói', 'Tệp JSON tối đa 256 KB'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('Quay lại lịch sử phiên bản', $view);
        $this->assertStringContainsString('Quay lại trình thiết kế', $view);
        $this->assertStringContainsString('Preview không ghi dữ liệu', $view);
        $this->assertStringContainsString('Import chỉ tạo bản nháp mới', $view);
        $this->assertStringContainsString('min-h-11', $view);

        $this->assertStringContainsString("Gate::authorize('exportDefinition', \$type)", $controller);
        $this->assertStringContainsString("Gate::authorize('importDefinition', \$type)", $controller);
        $this->assertStringContainsString("'package' => ['required', 'file', 'max:256']", $controller);
        $this->assertStringContainsString('preview_checksum', $controller);
        $this->assertStringContainsString('hash_equals', $controller);
        $this->assertStringContainsString('preview_checksum.', $controller);
        $this->assertStringContainsString('active_draft_exists', $dryRun);
        $this->assertStringContainsString('published_source_required', $dryRun);
        $this->assertStringContainsString('changed_sections', $dryRun);
        $this->assertStringContainsString('required_mappings', $dryRun);
        $this->assertStringNotContainsString('wire:click', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $controller.$dryRun.$view);
    }
}

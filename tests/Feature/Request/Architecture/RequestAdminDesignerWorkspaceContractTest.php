<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestAdminDesignerWorkspaceContractTest extends TestCase
{
    public function test_admin_designer_is_draft_first_structured_and_publish_safe(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Admin/TypeDesigner.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/type-designer.blade.php'));

        foreach (['Thông tin chung', 'Biểu mẫu', 'Phê duyệt & SLA', 'Đối tượng'] as $section) {
            $this->assertStringContainsString($section, $view);
        }

        foreach (['Trạng thái bản nháp', 'Số phần', 'Cấp duyệt', 'Xem lịch sử phiên bản'] as $summary) {
            $this->assertStringContainsString($summary, $view);
        }

        $this->assertStringContainsString('Bản nháp trên máy chủ là nguồn dữ liệu chính thức', $view);
        $this->assertStringContainsString('Phiên bản đã phát hành là bất biến', $view);
        $this->assertStringContainsString('wire:click="save"', $view);
        $this->assertStringContainsString('wire:click="publish"', $view);
        $this->assertStringContainsString('wire:confirm="{{ __(\'Request::request.publish_confirm\') }}"', $view);
        $this->assertStringContainsString('wire:confirm="Xóa phần này và toàn bộ trường bên trong?"', $view);
        $this->assertStringContainsString('wire:confirm="Xóa cấp duyệt này?"', $view);
        $this->assertStringContainsString('Nguyên tắc an toàn', $view);
        $this->assertStringContainsString('min-h-11', $view);

        $this->assertStringContainsString("Gate::authorize('update', \$type)", $component);
        $this->assertStringContainsString("Gate::authorize('publish', \$type)", $component);
        $this->assertStringContainsString('CreateTypeDraft::class', $component);
        $this->assertStringContainsString('SaveTypeDraft::class', $component);
        $this->assertStringContainsString('PublishTypeVersion', $component);
        $this->assertStringContainsString('UserDirectory::class', $component);
        $this->assertStringNotContainsString('App\\Models\\User', $component.$view);
    }
}

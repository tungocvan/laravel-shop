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

        foreach (['Bản nháp đang chỉnh sửa', 'Sẵn sàng phát hành', 'Số phần', 'Số trường', 'Cấp duyệt', 'Xem lịch sử phiên bản'] as $summary) {
            $this->assertStringContainsString($summary, $view);
        }

        foreach (['Lưu bản nháp', 'Phát hành phiên bản', 'Phát hành có tác động runtime', 'Cấu hình nâng cao · Audience JSON', 'Cấu hình nâng cao · Bộ phân giải JSON'] as $ux) {
            $this->assertStringContainsString($ux, $view);
        }

        foreach (['email_on_assignment', 'email_on_decision', 'email_on_sla_warning', 'timeout_action'] as $binding) {
            $this->assertStringContainsString('stages.{{ $stageIndex }}.'.$binding, $view);
        }

        $this->assertStringContainsString('Bản nháp trên máy chủ là nguồn dữ liệu chính thức', $view);
        $this->assertStringContainsString('Phiên bản đã phát hành là bất biến', $view);
        $this->assertStringContainsString('SLA không tự động phê duyệt hoặc từ chối đề nghị.', $view);
        $this->assertStringContainsString('wire:click="save"', $view);
        $this->assertStringContainsString('wire:click="publish"', $view);
        $this->assertStringContainsString('wire:confirm="{{ __(\'Request::request.publish_confirm\') }}"', $view);
        $this->assertStringContainsString('wire:confirm="Xóa phần này và toàn bộ trường bên trong?"', $view);
        $this->assertStringContainsString('wire:confirm="Xóa trường này khỏi biểu mẫu?"', $view);
        $this->assertStringContainsString('wire:confirm="Xóa cấp phê duyệt này?"', $view);
        $this->assertStringContainsString('min-h-11', $view);

        $this->assertStringContainsString("Gate::authorize('update', \$type)", $component);
        $this->assertStringContainsString("Gate::authorize('publish', \$type)", $component);
        $this->assertStringContainsString('CreateTypeDraft::class', $component);
        $this->assertStringContainsString('SaveTypeDraft::class', $component);
        $this->assertStringContainsString('PublishTypeVersion', $component);
        $this->assertStringContainsString('UserDirectory::class', $component);
        $this->assertStringContainsString('approvalReady', $component.$view);
        $this->assertStringContainsString('showValidationModal', $component.$view);
        $this->assertStringContainsString('presentValidationFailure', $component);
        $this->assertStringContainsString('role="alertdialog"', $view);
        $this->assertStringContainsString('Chưa thể phát hành phiên bản', $component);
        $this->assertStringContainsString('Quay lại chỉnh sửa', $view);
        $this->assertStringContainsString("'role_members'", $component);
        $this->assertStringContainsString('<option value="role_members">', $view);
        $this->assertStringNotContainsString('suspend_on_overdue', $view);
        $this->assertStringNotContainsString('email_notification_enabled', $view);
        $this->assertStringNotContainsString('<option value="fixed_role">', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $component.$view);
    }
}

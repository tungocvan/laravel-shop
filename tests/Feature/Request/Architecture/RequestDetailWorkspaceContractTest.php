<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestDetailWorkspaceContractTest extends TestCase
{
    public function test_request_detail_is_contextual_human_readable_and_action_safe(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Requester/RequestDetail.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/request-detail.blade.php'));

        foreach (['Trạng thái hiện tại', 'Nội dung đề nghị', 'Lịch sử xử lý', 'Trao đổi và tài liệu'] as $heading) {
            $this->assertStringContainsString($heading, $view);
        }

        foreach (['Bản nháp', 'Đang xử lý', 'Cần bổ sung', 'Đã hoàn tất', 'Đã từ chối', 'Đã hủy'] as $context) {
            $this->assertStringContainsString($context, $view);
        }

        foreach (['Đang chờ xử lý', 'Đã duyệt', 'Đã từ chối', 'Đã trả lại'] as $decision) {
            $this->assertStringContainsString($decision, $view);
        }

        $this->assertStringContainsString('Hành động của bạn', $view);
        $this->assertStringContainsString('$status === \'pending\' && $activeTask', $view);
        $this->assertStringContainsString('request.approver.decision-panel', $view);
        $this->assertStringContainsString('$status === \'returned\' && $isRequester', $view);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString("sortBy('stage_position')", $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringContainsString("Gate::authorize('view', \$request)", $component);
        $this->assertStringContainsString("Gate::authorize('submit', \$request)", $component);
        $this->assertStringContainsString("Gate::authorize('update', \$request)", $component);
        $this->assertStringContainsString("Gate::authorize('cancel', \$request)", $component);
        $this->assertStringNotContainsString('App\\Models\\User', $component.$view);
    }
}

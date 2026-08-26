<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestOperationsWorkspaceContractTest extends TestCase
{
    public function test_operations_workspace_is_failure_focused_allowlisted_and_recovery_safe(): void
    {
        $controller = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestOperationsController.php'));
        $query = file_get_contents(base_path('Modules/Request/Application/Services/RequestOperationsQuery.php'));
        $retry = file_get_contents(base_path('Modules/Request/Application/Services/RetryRequestOperation.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/admin/operations.blade.php'));

        foreach (['Trung tâm phục hồi vận hành', 'Sự cố có thể thử lại', 'Kích hoạt bước xử lý', 'Phân phối outbox', 'Tạo tệp xuất', 'Số lần thử', 'Cập nhật gần nhất'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        foreach (['Chỉ các tác vụ trong danh sách cho phép mới có thể chạy lại', 'Thử lại tác vụ', 'Hành động phục hồi được ghi nhận audit', 'Không chạy lệnh tùy ý'] as $safetyCopy) {
            $this->assertStringContainsString($safetyCopy, $view);
        }

        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringContainsString("Rule::in((array) config('request.operations.retry_allowlist', []))", $controller);
        $this->assertStringContainsString("'idempotency_key' => ['required', 'string', 'min:8', 'max:200']", $controller);
        $this->assertStringContainsString("abort_unless(\$policy->retry(\$request->user('admin')), 403)", $controller);
        $this->assertStringContainsString("config('request.operations.page_size', 25)", $query);
        $this->assertStringContainsString("config('request.operations.max_page_size', 100)", $query);
        $this->assertStringContainsString('$this->idempotency->execute', $retry);
        $this->assertStringContainsString("request.operation.outbox_retried.v1", $retry);
        $this->assertStringContainsString("request.operation.export_retried.v1", $retry);
        $this->assertStringNotContainsString('command', strtolower($controller.$view));
        $this->assertStringNotContainsString('App\\Models\\User', $controller.$query.$retry.$view);
    }
}

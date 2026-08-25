<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestApproverPendingWorkspaceContractTest extends TestCase
{
    public function test_approver_pending_workspace_is_workload_and_sla_oriented(): void
    {
        $query = file_get_contents(base_path('Modules/Request/Application/Queries/ApproverInboxQuery.php'));
        $component = file_get_contents(base_path('Modules/Request/Livewire/Approver/Inbox.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/inbox.blade.php'));

        foreach (['pending', 'processed', 'all'] as $mode) {
            $this->assertStringContainsString("'{$mode}'", $component.$query);
        }

        foreach (['Chờ bạn duyệt', 'Sắp quá hạn', 'Đã quá hạn', 'Đã tạm dừng'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('workloadSummary', $component.$query);
        $this->assertStringContainsString("where('assignee_user_id', \$userId)", $query);
        $this->assertStringContainsString("whereColumn('current_stage_position', 'request_tasks.stage_position')", $query);
        $this->assertStringContainsString("orderByRaw('CASE WHEN suspended_at IS NOT NULL", $query);
        $this->assertStringContainsString("oldest('due_at')", $query);
        $this->assertStringContainsString('Xem và xử lý', $view);
        $this->assertStringContainsString('Xem lịch sử', $view);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringNotContainsString('REQUEST_UI_DEMO', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $query.$component);
    }
}

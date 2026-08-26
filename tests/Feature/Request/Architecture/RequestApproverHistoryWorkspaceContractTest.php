<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestApproverHistoryWorkspaceContractTest extends TestCase
{
    public function test_processed_workspace_is_decision_oriented_and_read_only(): void
    {
        $query = file_get_contents(base_path('Modules/Request/Application/Queries/ApproverInboxQuery.php'));
        $component = file_get_contents(base_path('Modules/Request/Livewire/Approver/Inbox.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/inbox.blade.php'));

        foreach (['all', 'approved', 'rejected', 'returned'] as $decision) {
            $this->assertStringContainsString("'{$decision}'", $component.$query);
        }

        foreach (['Tất cả đã xử lý', 'Đã duyệt', 'Đã từ chối', 'Đã trả lại', 'Quyết định lúc:', 'Xem lịch sử'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('processedSummary', $component.$query);
        $this->assertStringContainsString("where('assignee_user_id', \$userId)", $query);
        $this->assertStringContainsString("whereIn('status', [TaskStatus::Approved->value, TaskStatus::Rejected->value, TaskStatus::Returned->value])", $query);
        $this->assertStringContainsString("selectRaw('status, COUNT(*) as aggregate')", $query);
        $this->assertStringContainsString("groupBy('status')", $query);
        $this->assertStringContainsString("orderByDesc('decided_at')", $query);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString('$isPending ? \'Xem và xử lý\' : \'Xem lịch sử\'', $view);
        $this->assertStringNotContainsString('wire:click="approve', $view);
        $this->assertStringNotContainsString('wire:click="reject', $view);
        $this->assertStringNotContainsString('wire:click="return', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $query.$component);
    }
}

<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestReportsExportWorkspaceContractTest extends TestCase
{
    public function test_reports_workspace_is_filterable_explainable_responsive_and_export_safe(): void
    {
        $reportController = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestReportController.php'));
        $exportController = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestExportController.php'));
        $query = file_get_contents(base_path('Modules/Request/Application/Services/RequestExportQuery.php'));
        $planner = file_get_contents(base_path('Modules/Request/Application/Services/PlanRequestExport.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/admin/reports.blade.php'));

        foreach (['Không gian báo cáo Đề nghị', 'Bộ lọc báo cáo', 'Phân bố theo trạng thái', 'Xem lại phạm vi trước khi xuất', 'Sổ đăng ký đề nghị', 'Tệp xuất gần đây'] as $workspaceCopy) {
            $this->assertStringContainsString($workspaceCopy, $view);
        }

        foreach (['name="group_public_id"', 'name="type_public_id"', 'name="status"', 'name="created_from"', 'name="created_to"', 'name="per_page"'] as $filterControl) {
            $this->assertStringContainsString($filterControl, $view);
        }

        foreach (['name="confirmed"', 'selected_request_public_ids[]', 'nếu không chọn dòng nào', 'quyền tải xuống sẽ được kiểm tra lại', 'Tạo tệp xuất an toàn'] as $exportSafety) {
            $this->assertStringContainsString($exportSafety, $view);
        }

        $this->assertStringContainsString('id="request-export-form"', $view);
        $this->assertStringContainsString('form="request-export-form"', $view);
        $this->assertStringContainsString('Checkbox chỉ chọn các dòng của trang đang hiển thị', $view);
        $this->assertStringContainsString('md:hidden', $view);
        $this->assertStringContainsString('hidden overflow-x-auto md:block', $view);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString('role="status"', $view);
        $this->assertStringContainsString('focus:ring-2', $view);
        $this->assertStringContainsString('min-h-11', $view);

        $this->assertStringContainsString("config('request.settings.page_sizes'", $reportController);
        $this->assertStringContainsString("Rule::in(\$pageSizes)", $reportController);
        $this->assertStringContainsString("Rule::when(\$httpRequest->filled('created_from'), 'after_or_equal:created_from')", $reportController);
        $this->assertStringContainsString("->only(['status', 'type_public_id', 'group_public_id', 'created_from', 'created_to'])", $reportController);
        $this->assertStringContainsString("'selected_request_public_ids' => ['nullable', 'array'", $exportController);
        $this->assertStringContainsString("'selected_request_public_ids.*' => ['required', 'ulid', 'distinct']", $exportController);
        $this->assertStringContainsString("'confirmed' => ['required', 'accepted']", $exportController);
        $this->assertStringContainsString("Rule::when(\$request->filled('created_from'), 'after_or_equal:created_from')", $exportController);
        $this->assertStringContainsString("\$exportFilters['request_public_ids'] = \$selectedRequestPublicIds", $exportController);
        $this->assertStringContainsString("whereHas('type.group'", $query);
        $this->assertStringContainsString("whereIn('request_instances.public_id', \$filters['request_public_ids'])", $query);
        $this->assertStringContainsString('localDayBoundary', $query);
        $this->assertStringContainsString("config('app.timezone', 'UTC')", $query);
        $this->assertStringContainsString("['type_public_id', 'group_public_id', 'request_public_id']", $planner);
        $this->assertStringContainsString("array_key_exists('request_public_ids', \$filters)", $planner);
        $this->assertStringContainsString('invalidFilters', $planner);
        $this->assertStringNotContainsString('payload_json', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $reportController.$exportController.$query.$planner.$view);
    }
}

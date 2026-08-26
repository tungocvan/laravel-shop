<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestResponsiveUiContractTest extends TestCase
{
    public function test_designer_has_keyboard_ordering_responsive_layout_and_version_review(): void
    {
        $designer = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/type-designer.blade.php'));
        $versions = file_get_contents(base_path('Modules/Request/resources/views/admin/versions.blade.php'));

        $this->assertStringContainsString('moveSection', $designer);
        $this->assertStringContainsString('moveField', $designer);
        $this->assertStringContainsString('moveStage', $designer);
        $this->assertStringContainsString('xl:grid-cols-[14rem_minmax(0,1fr)_19rem]', $designer);
        $this->assertStringContainsString('xl:sticky xl:top-4 xl:self-start', $designer);
        $this->assertStringContainsString('Sẵn sàng phát hành', $designer);
        $this->assertStringContainsString('min-h-11', $designer);
        $this->assertStringContainsString('grid min-w-0 gap-4 md:grid-cols-2', $designer);
        $this->assertStringContainsString('grid-cols-[minmax(0,1fr)_minmax(5rem,auto)]', $designer);
        $this->assertStringContainsString('min-h-11 min-w-0 w-full', $designer);
        $this->assertStringNotContainsString('<div class="grid gap-4 lg:grid-cols-3">', $designer);
        $this->assertStringContainsString('Xem chi tiết định nghĩa chuẩn hóa', $versions);
        $this->assertStringNotContainsString('draggable=', $designer);
    }

    public function test_request_detail_exposes_reviewed_local_draft_contract_without_mutation_replay(): void
    {
        $detail = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/request-detail.blade.php'));
        $runtime = file_get_contents(base_path('Modules/Request/resources/js/request-offline.js'));

        $this->assertStringContainsString('data-request-draft-form', $detail);
        $this->assertStringContainsString('data-request-offline-draft', $detail);
        $this->assertStringContainsString('data-request-restore-draft', $detail);
        $this->assertStringContainsString('server_lock_version', $runtime);
        $this->assertStringContainsString("setState('conflict')", $runtime);
        $this->assertStringNotContainsString('Background Sync', $runtime);
        $this->assertStringNotContainsString('.submit()', $runtime);
    }

    public function test_request_form_supports_compact_field_widths_multi_upload_and_private_storage_bootstrap(): void
    {
        $detail = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/request-detail.blade.php'));
        $attachments = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/attachment-manager.blade.php'));
        $designer = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/type-designer.blade.php'));
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $entrypoint = file_get_contents(base_path('docker/entrypoint.sh'));

        $this->assertStringContainsString("'third' => 'sm:col-span-1 lg:col-span-2'", $detail);
        $this->assertStringContainsString('Không bắt buộc', $detail);
        $this->assertStringContainsString('Mặc định ngày hôm nay', $designer);
        $this->assertStringContainsString('Độ rộng hiển thị', $designer);
        $this->assertStringContainsString('Phân cách hàng nghìn, không có số lẻ', $designer);
        $this->assertStringContainsString('addFieldOption', $designer);
        $this->assertStringContainsString('Danh sách lựa chọn', $designer);
        $this->assertStringContainsString('data-request-grouped-integer', $detail);
        $this->assertStringContainsString('formatGroupedInteger', file_get_contents(base_path('Modules/Request/resources/js/request-offline.js')));
        $this->assertStringContainsString('type="file" multiple', $attachments);
        $this->assertStringContainsString('storage/app/request/attachments', $dockerfile);
        $this->assertStringContainsString('storage/app/request/attachments', $entrypoint);
    }
}

<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestDefinitionManagementWorkspaceContractTest extends TestCase
{
    public function test_definition_management_is_searchable_status_aware_and_designer_oriented(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Admin/DefinitionIndex.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/definition-index.blade.php'));

        foreach (['Quản lý loại đề nghị', 'Tạo nhóm đề nghị', 'Tạo loại đề nghị', 'Tìm kiếm & lọc', 'Danh sách loại đề nghị'] as $heading) {
            $this->assertStringContainsString($heading, $view);
        }

        foreach (['Tất cả trạng thái', 'Bản nháp', 'Đang phát hành', 'Ngừng sử dụng'] as $status) {
            $this->assertStringContainsString($status, $view);
        }

        foreach (['Mở Designer', 'Lịch sử phiên bản', 'Phiên bản hiện hành', 'Bản nháp hiện tại'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString("public string $status = ''", $component);
        $this->assertStringContainsString('updatedStatus', $component);
        $this->assertStringContainsString("with(['group:id,name', 'activeDraft:id,version_number', 'currentPublishedVersion:id,version_number'])", $component);
        $this->assertStringContainsString("when($this->status !== ''", $component);
        $this->assertStringContainsString("paginate(25)", $component);
        $this->assertStringContainsString("Gate::authorize('viewAny', RequestType::class)", $component);
        $this->assertStringContainsString("Gate::authorize('create', RequestGroup::class)", $component);
        $this->assertStringContainsString("Gate::authorize('create', RequestType::class)", $component);
        $this->assertStringContainsString('wire:model.live="status"', $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $component.$view);
    }
}

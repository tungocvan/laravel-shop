<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestVersionHistoryWorkspaceContractTest extends TestCase
{
    public function test_version_history_is_release_oriented_traceable_and_read_only(): void
    {
        $controller = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestDefinitionController.php'));
        $model = file_get_contents(base_path('Modules/Request/Models/RequestTypeVersion.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/admin/versions.blade.php'));

        foreach (['Lịch sử phiên bản', 'Phiên bản hiện hành', 'Bản nháp hiện tại', 'Dòng thời gian phát hành', 'So sánh với phiên bản trước'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        foreach (['Bản nháp', 'Đã phát hành', 'Đã ngừng sử dụng', 'Người tạo', 'Người phát hành', 'Phát hành lúc'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('Quay lại quản lý loại đề nghị', $view);
        $this->assertStringContainsString('Quay lại trình thiết kế', $view);
        $this->assertStringContainsString('Xem chi tiết định nghĩa chuẩn hóa', $view);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringNotContainsString('wire:model', $view);
        $this->assertStringNotContainsString('wire:click', $view);

        $this->assertStringContainsString('use Modules\\User\\Contracts\\UserDirectory;', $controller);
        $this->assertStringContainsString('UserDirectory $users', $controller);
        $this->assertStringContainsString('findManyActive($actorIds, 100)', $controller);
        $this->assertStringContainsString("Gate::authorize('view', \$type)", $controller);
        $this->assertStringContainsString('Published Request type versions are immutable.', $model);
        $this->assertStringContainsString('Published Request type versions cannot be deleted.', $model);
        $this->assertStringNotContainsString('App\\Models\\User', $controller.$model.$view);
    }
}

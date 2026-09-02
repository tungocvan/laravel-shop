<?php

namespace Modules\Role\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Role\Services\RolePermissionCatalogService;
use Modules\Role\Services\RoleService;

class RoleTable extends Component
{
    use WithPagination;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public int $perPage = 10;

    public array $selected = [];

    public bool $selectAll = false;

    public bool $showPermissionModal = false;

    public bool $showSyncModal = false;

    public array $syncPreview = [];

    public string $newModuleName = '';

    public array $newModuleActions = [
        'view' => true,
        'create' => true,
        'edit' => true,
        'delete' => true,
        'export' => false,
    ];

    public function mount(): void
    {
        $this->authorizeCapability('view_role');
        $this->perPage = $this->normalizedPerPage($this->perPage);
    }

    public function previewPermissionSync(RolePermissionCatalogService $catalog): void
    {
        $this->authorizeSuperAdmin();
        $this->syncPreview = $catalog->previewActiveSync();
        $this->showSyncModal = true;
    }

    public function syncModulePermissions(RolePermissionCatalogService $catalog): void
    {
        $this->authorizeSuperAdmin();
        $result = $catalog->syncAllActiveToSuperAdmin();
        $this->syncPreview = $catalog->previewActiveSync();
        $this->showSyncModal = false;

        $this->dispatch(
            'notify',
            content: "Đã đồng bộ {$result['modules_with_permissions']} module: tạo {$result['created']} quyền mới, bổ sung {$result['assigned']} quyền cho Super Admin. Tổng catalog {$result['total']} quyền.",
            type: 'success'
        );
    }

    public function openPermissionModal(): void
    {
        $this->authorizeSuperAdmin();
        $this->reset(['newModuleName']);
        $this->newModuleActions = [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'export' => false,
        ];
        $this->showPermissionModal = true;
    }

    public function createModulePermissions(RolePermissionCatalogService $catalog): void
    {
        $this->authorizeSuperAdmin();
        $this->validate([
            'newModuleName' => ['required', 'alpha_dash', 'min:2'],
            'newModuleActions' => ['array'],
            'newModuleActions.*' => ['boolean'],
        ]);

        $result = $catalog->createDeclaredPermissions($this->newModuleName, $this->newModuleActions);

        if (! $result['ok']) {
            $field = $result['reason'] === 'module_not_found' ? 'newModuleName' : 'newModuleActions';
            $message = $result['reason'] === 'module_not_found'
                ? 'Module này không tồn tại trong catalog module đang hoạt động.'
                : 'Một hoặc nhiều quyền được chọn không được module khai báo trong catalog.';
            $this->addError($field, $message);

            return;
        }

        $this->showPermissionModal = false;
        $created = (int) $result['created'];
        $module = (string) $result['module'];
        $this->dispatch(
            'notify',
            content: $created > 0
                ? "Đã đồng bộ {$created} quyền được module '{$module}' khai báo."
                : "Các quyền được module '{$module}' khai báo đã tồn tại.",
            type: $created > 0 ? 'success' : 'warning'
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizedPerPage($value);
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->resetSelection();

            return;
        }

        $this->selected = app(RoleService::class)
            ->queryRoles($this->search)
            ->paginate($this->normalizedPerPage($this->perPage))
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();
    }

    public function updatedSelected(): void
    {
        $this->selected = collect($this->selected)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->map(fn (int $id): string => (string) $id)
            ->values()
            ->all();
        $this->selectAll = false;
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function deleteSelected(RoleService $roles): void
    {
        $this->authorizeCapability('delete_role');
        $result = $roles->deleteMany(array_map('intval', $this->selected));
        $this->resetSelection();
        $type = $result['deleted'] > 0 ? ($result['blocked'] > 0 ? 'warning' : 'success') : 'error';
        $content = $result['deleted'] > 0
            ? "Đã xóa {$result['deleted']} vai trò.".($result['blocked'] > 0 ? " {$result['blocked']} vai trò bị chặn." : '')
            : 'Không thể xóa vai trò đang có tài khoản sử dụng hoặc vai trò Super Admin.';
        $this->dispatch('notify', content: $content, type: $type);
    }

    public function delete(int $id, RoleService $roles): void
    {
        $this->authorizeCapability('delete_role');
        $result = $roles->delete($id);

        if ($result === 'protected') {
            $this->dispatch('notify', content: 'Không thể xóa Super Admin!', type: 'error');

            return;
        }

        if ($result === 'in_use') {
            $this->dispatch('notify', content: 'Không thể xóa vai trò vì đang có tài khoản sử dụng.', type: 'error');

            return;
        }

        $this->dispatch('notify', content: 'Đã xóa vai trò.', type: 'success');
    }

    public function render(RoleService $roles)
    {
        $this->authorizeCapability('view_role');
        $this->perPage = $this->normalizedPerPage($this->perPage);

        return view('Role::livewire.role-table', [
            'roles' => $roles->queryRoles($this->search)->paginate($this->perPage),
            'exportFilters' => [
                'search' => $this->search,
                'selected_ids' => array_map('intval', $this->selected),
            ],
        ]);
    }

    private function normalizedPerPage(mixed $value): int
    {
        $perPage = (int) $value;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    private function authorizeCapability(string $permission): void
    {
        $actor = auth('admin')->user();
        abort_unless(auth('admin')->check() && $actor?->can($permission), 403);
    }

    private function authorizeSuperAdmin(): void
    {
        $actor = auth('admin')->user();
        abort_unless(auth('admin')->check() && $actor?->hasRole(RoleService::PROTECTED_ROLE), 403);
    }
}

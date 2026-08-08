<?php

namespace Modules\Admin\Livewire\Menus;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\MenuImportExportService;
use Modules\Admin\Services\MenuService;

class MenuTable extends Component
{
    use WithFileUploads;

    public string $search = '';

    public string $filterStatus = 'active';

    public array $selectedMenus = [];

    public bool $selectAll = false;

    public bool $showImportModal = false;

    public $importFile = null;

    public ?array $importReport = null;

    public bool $showBulkPermissionsModal = false;

    public ?string $bulkPermission = null;

    protected $queryString = ['search', 'filterStatus'];

    protected function rules(): array
    {
        return [
            'importFile' => 'nullable|file|mimes:xlsx,csv|max:' . config('menu.import.max_file_size', 10240),
            'bulkPermission' => 'nullable|exists:permissions,name',
        ];
    }

    public function updatedSelectAll($value): void
    {
        $this->selectedMenus = $value
            ? $this->menus()->idsForSelection($this->filters())
            : [];
    }

    public function updatedImportFile(): void
    {
        $this->resetErrorBag('importFile');
        $this->importReport = null;
    }

    public function getImportFileNameProperty(): ?string
    {
        return $this->importFile?->getClientOriginalName();
    }

    public function restoreDefaultMenu(): void
    {
        $this->authorizePermission('admin.menu.restore');

        $report = $this->imports()->restoreDefaults();
        $this->importReport = $report;

        if (($report['success'] ?? false) !== true) {
            $this->notify('Khoi phuc menu mac dinh that bai. Vui long kiem tra report.', 'error');
            return;
        }

        $this->dispatch(
            'notify',
            content: "Khoi phuc menu mac dinh hoan tat: {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.",
            type: 'success',
            action: 'reload',
            duration: 100
        );
    }

    public function closeImportModal(): void
    {
        $this->reset(['showImportModal', 'importFile', 'importReport']);
        $this->resetValidation();
    }

    public function delete($id): void
    {
        $this->authorizePermission('admin.menu.delete');

        if (! $this->menus()->delete($id)) {
            return;
        }

        $this->dispatch('notify', content: 'Da xoa menu thanh cong.', type: 'success', action: 'reload');
    }

    public function toggleStatus($id): void
    {
        $this->authorizePermission('admin.menu.update');

        if (! $this->menus()->toggleStatus($id)) {
            return;
        }

        $this->dispatch('notify', content: 'Da cap nhat trang thai menu.', type: 'success');
    }

    public function duplicate($id): void
    {
        $this->authorizePermission('admin.menu.create');

        if (! $this->menus()->duplicate($id)) {
            $this->notify('Menu khong ton tai.', 'warning');
            return;
        }

        $this->dispatch('notify', content: 'Da nhan ban menu thanh cong.', type: 'success', action: 'reload');
    }

    public function bulkDelete(): void
    {
        $this->authorizePermission('admin.menu.delete');

        if (empty($this->selectedMenus)) {
            $this->dispatch('notify', content: 'Vui long chon menu can xoa.', type: 'warning');
            return;
        }

        $count = $this->menus()->bulkDelete($this->selectedMenus);

        $this->resetSelection();

        $this->dispatch('notify', content: "Da xoa {$count} menu thanh cong.", type: 'success', action: 'reload');
    }

    public function bulkToggleStatus($status): void
    {
        $this->authorizePermission('admin.menu.update');

        if (empty($this->selectedMenus)) {
            $this->notify('Vui long chon menu.', 'warning');
            return;
        }

        $count = $this->menus()->bulkToggleStatus($this->selectedMenus, (bool) $status);

        $this->resetSelection();

        $this->notify("Da cap nhat {$count} menu.");
    }

    public function openBulkPermissionsModal(): void
    {
        if (empty($this->selectedMenus)) {
            $this->notify('Vui long chon menu.', 'warning');
            return;
        }

        $this->showBulkPermissionsModal = true;
    }

    public function bulkAssignPermissions(): void
    {
        $this->authorizePermission('admin.menu.update');

        if (empty($this->selectedMenus)) {
            $this->dispatch('notify', content: 'Vui long chon menu can cap nhat.', type: 'warning');
            return;
        }

        $this->validate([
            'bulkPermission' => 'nullable|exists:permissions,name',
        ]);

        $count = $this->menus()->bulkAssignPermission($this->selectedMenus, $this->bulkPermission);
        $permissionName = $this->bulkPermission ?: 'khong co';

        $this->resetSelection();
        $this->showBulkPermissionsModal = false;
        $this->bulkPermission = null;

        $this->dispatch(
            'notify',
            content: "Da cap nhat quyen cho {$count} menu thanh '{$permissionName}'.",
            type: 'success',
            action: 'reload'
        );
    }

    public function updateMenuOrder($list): void
    {
        $this->authorizePermission('admin.menu.update');

        try {
            $this->menus()->updateOrder((array) $list);
        } catch (\InvalidArgumentException $exception) {
            $this->notify($exception->getMessage(), 'error');
            return;
        }

        $this->dispatch(
            'notify',
            content: 'Da cap nhat thu tu menu.',
            type: 'success',
            action: 'reload',
            duration: 100
        );
    }

    public function export()
    {
        $this->authorizePermission('admin.menu.export');

        try {
            $path = $this->imports()->export($this->filters());

            return Storage::disk('public')->download($path);
        } catch (\Throwable $e) {
            report($e);

            $this->notify('Loi export menu. Vui long kiem tra log.', 'error');
        }
    }

    public function exportTemplate()
    {
        $this->authorizePermission('admin.menu.export');

        try {
            $path = $this->imports()->exportTemplate();

            return Storage::disk('public')->download($path);
        } catch (\Throwable $e) {
            report($e);

            $this->notify('Loi tao file mau menu. Vui long kiem tra log.', 'error');
        }
    }

    public function import(): void
    {
        $this->authorizePermission('admin.menu.import');

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,csv|max:' . config('menu.import.max_file_size', 10240),
        ]);

        $report = $this->imports()->importFromFile(
            $this->importFile->getRealPath(),
            ['mode' => 'skip_duplicate']
        );

        $this->importReport = $report;

        if (($report['success'] ?? false) !== true) {
            $this->addError('importFile', 'Import menu co loi. Vui long kiem tra report ben duoi.');
            return;
        }

        $this->reset(['showImportModal', 'importFile']);
        $this->notify("Import menu hoan tat: {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.");
    }

    public function render()
    {
        $stats = $this->menus()->stats($this->filters());

        return view('Admin::livewire.menus.menu-table', [
            'menus' => $this->menus()->rootTree($this->filters()),
            'totalMenus' => $stats['totalMenus'],
            'activeMenus' => $stats['activeMenus'],
        ]);
    }

    private function resetSelection(): void
    {
        $this->selectedMenus = [];
        $this->selectAll = false;
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->filterStatus,
        ];
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', content: $message, type: $type);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }

    private function menus(): MenuService
    {
        return app(MenuService::class);
    }

    private function imports(): MenuImportExportService
    {
        return app(MenuImportExportService::class);
    }
}

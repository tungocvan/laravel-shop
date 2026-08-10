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

    protected MenuService $menuService;

    protected MenuImportExportService $importExportService;

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

    public function boot(MenuService $menuService, MenuImportExportService $importExportService): void
    {
        $this->menuService = $menuService;
        $this->importExportService = $importExportService;
    }

    protected function rules(): array
    {
        return [
            'importFile' => 'nullable|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240),
            'bulkPermission' => 'nullable|exists:permissions,name',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetSelection();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedMenus = $value
            ? $this->menuService->idsForSelection($this->filters())
            : [];
    }

    public function updatedSelectedMenus(): void
    {
        $visible = $this->menuService->idsForSelection($this->filters());
        $selected = array_values(array_intersect($visible, array_map('strval', $this->selectedMenus)));

        $this->selectedMenus = $selected;
        $this->selectAll = $visible !== [] && count($selected) === count($visible);
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

    public function openImportModal(): void
    {
        $this->authorizePermission('admin.menu.import');
        $this->resetErrorBag('importFile');
        $this->importFile = null;
        $this->importReport = null;
        $this->showImportModal = true;
    }

    public function restoreDefaultMenu(): void
    {
        $this->authorizePermission('admin.menu.restore');

        try {
            $report = $this->importExportService->restoreDefaults();
            $this->importReport = $this->publicImportReport($report);

            if (($report['success'] ?? false) !== true) {
                $this->notify('Khoi phuc menu mac dinh that bai. Vui long kiem tra report.', 'error');

                return;
            }

            $this->notify(
                "Khoi phuc menu mac dinh hoan tat: {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.",
                'success',
                'reload',
                100,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->notify('Khoi phuc menu mac dinh that bai. Vui long kiem tra log.', 'error');
        }
    }

    public function closeImportModal(): void
    {
        $this->reset(['showImportModal', 'importFile']);
        $this->resetValidation();
    }

    public function delete($id): void
    {
        $this->authorizePermission('admin.menu.delete');

        if (! $this->menuService->delete($id)) {
            return;
        }

        $this->notify('Da xoa menu thanh cong.', 'success', 'reload');
    }

    public function toggleStatus($id): void
    {
        $this->authorizePermission('admin.menu.update');

        if (! $this->menuService->toggleStatus($id)) {
            return;
        }

        $this->notify('Da cap nhat trang thai menu.');
    }

    public function duplicate($id): void
    {
        $this->authorizePermission('admin.menu.create');

        if (! $this->menuService->duplicate($id)) {
            $this->notify('Menu khong ton tai.', 'warning');

            return;
        }

        $this->notify('Da nhan ban menu thanh cong.', 'success', 'reload');
    }

    public function bulkDelete(): void
    {
        $this->authorizePermission('admin.menu.delete');

        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu can xoa.', 'warning');

            return;
        }

        $count = $this->menuService->bulkDelete($this->selectedMenus);
        $this->resetSelection();
        $this->notify("Da xoa {$count} menu thanh cong.", 'success', 'reload');
    }

    public function bulkToggleStatus($status): void
    {
        $this->authorizePermission('admin.menu.update');

        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu.', 'warning');

            return;
        }

        $count = $this->menuService->bulkToggleStatus($this->selectedMenus, (bool) $status);
        $this->resetSelection();
        $this->notify("Da cap nhat {$count} menu.");
    }

    public function openBulkPermissionsModal(): void
    {
        $this->authorizePermission('admin.menu.update');

        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu.', 'warning');

            return;
        }

        $this->showBulkPermissionsModal = true;
    }

    public function closeBulkPermissionsModal(): void
    {
        $this->showBulkPermissionsModal = false;
        $this->bulkPermission = null;
        $this->resetValidation('bulkPermission');
    }

    public function bulkAssignPermissions(): void
    {
        $this->authorizePermission('admin.menu.update');

        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu can cap nhat.', 'warning');

            return;
        }

        $this->validate([
            'bulkPermission' => 'nullable|exists:permissions,name',
        ]);

        $count = $this->menuService->bulkAssignPermission($this->selectedMenus, $this->bulkPermission);
        $permissionName = $this->bulkPermission ?: 'khong co';

        $this->resetSelection();
        $this->closeBulkPermissionsModal();
        $this->notify("Da cap nhat quyen cho {$count} menu thanh '{$permissionName}'.", 'success', 'reload');
    }

    public function updateMenuOrder($list): void
    {
        $this->authorizePermission('admin.menu.update');

        try {
            $this->menuService->updateOrder((array) $list);
        } catch (\InvalidArgumentException $exception) {
            $this->notify($exception->getMessage(), 'error');

            return;
        }

        $this->notify('Da cap nhat thu tu menu.', 'success', 'reload', 100);
    }

    public function export()
    {
        $this->authorizePermission('admin.menu.export');

        try {
            $path = $this->importExportService->export($this->filters());

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
            $path = $this->importExportService->exportTemplate();

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
            'importFile' => 'required|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240),
        ]);

        try {
            $report = $this->importExportService->importFromFile(
                $this->importFile->getRealPath(),
                ['mode' => 'skip_duplicate']
            );

            $this->importReport = $this->publicImportReport($report);

            if (($report['success'] ?? false) !== true) {
                $this->addError('importFile', 'Import menu co loi. Vui long kiem tra report ben duoi.');

                return;
            }

            $this->reset(['showImportModal', 'importFile']);
            $this->notify("Import menu hoan tat: {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.");
        } catch (\Throwable $e) {
            report($e);
            $this->addError('importFile', 'Import menu that bai. Vui long kiem tra log he thong.');
        }
    }

    public function render()
    {
        $stats = $this->menuService->stats($this->filters());

        return view('Admin::livewire.menus.menu-table', [
            'menus' => $this->menuService->rootTree($this->filters()),
            'totalMenus' => $stats['totalMenus'],
            'activeMenus' => $stats['activeMenus'],
            'permissionOptions' => $this->menuService->permissionOptions(),
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

    private function notify(
        string $message,
        string $type = 'success',
        ?string $action = null,
        ?int $duration = null,
    ): void {
        $payload = ['content' => $message, 'type' => $type];

        if ($action !== null) {
            $payload['action'] = $action;
        }

        if ($duration !== null) {
            $payload['duration'] = $duration;
        }

        $this->dispatch('notify', ...$payload);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }

    private function publicImportReport(array $report): array
    {
        return [
            'success' => (bool) ($report['success'] ?? false),
            'total_rows' => (int) ($report['total_rows'] ?? 0),
            'success_rows' => (int) ($report['success_rows'] ?? 0),
            'error_rows' => (int) ($report['error_rows'] ?? 0),
            'skipped_rows' => (int) ($report['skipped_rows'] ?? 0),
            'errors' => array_values(array_map(
                static fn (array $error): array => [
                    'row' => $error['row'] ?? null,
                    'column' => $error['column'] ?? null,
                    'value' => $error['value'] ?? null,
                    'reason' => $error['reason'] ?? 'Du lieu khong hop le.',
                ],
                array_filter($report['errors'] ?? [], 'is_array')
            )),
        ];
    }
}

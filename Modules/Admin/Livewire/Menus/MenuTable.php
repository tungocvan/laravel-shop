<?php

namespace Modules\Admin\Livewire\Menus;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\MenuImportExportService;
use Modules\Admin\Services\MenuRouteScannerService;
use Modules\Admin\Services\MenuService;

class MenuTable extends Component
{
    use WithFileUploads;

    protected MenuService $menuService;
    protected MenuImportExportService $importExportService;
    protected MenuRouteScannerService $routeScannerService;

    public string $search = '';
    public string $filterStatus = 'active';
    public array $selectedMenus = [];
    public bool $selectAll = false;
    public bool $showImportModal = false;
    public $importFile = null;
    public string $importMode = 'skip_duplicate';
    public ?array $importReport = null;
    public bool $showBulkPermissionsModal = false;
    public bool $showBulkDeleteModal = false;
    public bool $showRouteScannerModal = false;
    public array $routeCandidates = [];
    public array $selectedRouteCandidates = [];
    public array $routeCandidateNames = [];
    public ?string $bulkPermission = null;

    protected $queryString = ['search', 'filterStatus'];

    public function boot(
        MenuService $menuService,
        MenuImportExportService $importExportService,
        MenuRouteScannerService $routeScannerService,
    ): void {
        $this->menuService = $menuService;
        $this->importExportService = $importExportService;
        $this->routeScannerService = $routeScannerService;
    }

    protected function rules(): array
    {
        return [
            'importFile' => 'nullable|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240),
            'importMode' => 'required|in:skip_duplicate,update_or_create',
            'bulkPermission' => 'nullable|exists:permissions,name',
            'routeCandidateNames.*' => 'nullable|string|max:255',
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
        $this->selectedMenus = $value ? $this->menuService->idsForSelection($this->filters()) : [];
    }

    public function toggleMenuSelection(int|string $menuId): void
    {
        $branchIds = $this->menuService->idsForBranch($menuId);
        if ($branchIds === []) {
            return;
        }

        $selected = array_map('strval', $this->selectedMenus);
        $rootId = (string) $menuId;
        $shouldSelect = ! in_array($rootId, $selected, true);

        $this->selectedMenus = $shouldSelect
            ? array_values(array_unique(array_merge($selected, $branchIds)))
            : array_values(array_diff($selected, $branchIds));

        $visible = $this->menuService->idsForSelection($this->filters());
        $this->selectAll = $visible !== [] && count(array_intersect($visible, $this->selectedMenus)) === count($visible);
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
        $this->importMode = 'skip_duplicate';
        $this->importReport = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->reset(['showImportModal', 'importFile']);
        $this->importMode = 'skip_duplicate';
        $this->resetValidation();
    }

    public function openRouteScannerModal(): void
    {
        $this->authorizePermission('admin.menu.view');
        $this->routeCandidates = $this->routeScannerService->candidates();
        $this->selectedRouteCandidates = [];
        $this->routeCandidateNames = collect($this->routeCandidates)
            ->mapWithKeys(fn (array $candidate): array => [(string) $candidate['id'] => (string) $candidate['name']])
            ->all();
        $this->showRouteScannerModal = true;
    }

    public function closeRouteScannerModal(): void
    {
        $this->showRouteScannerModal = false;
        $this->routeCandidates = [];
        $this->selectedRouteCandidates = [];
        $this->routeCandidateNames = [];
    }

    public function selectAllRouteCandidates(): void
    {
        $this->selectedRouteCandidates = array_values(array_map(
            fn (array $candidate): string => (string) $candidate['id'],
            $this->routeCandidates,
        ));
    }

    public function addSelectedRouteCandidates(): void
    {
        $this->authorizePermission('admin.menu.create');

        if ($this->selectedRouteCandidates === []) {
            $this->notify('Vui long chon it nhat mot route de them vao menu.', 'warning');
            return;
        }

        $this->validate(['routeCandidateNames.*' => 'nullable|string|max:255']);

        try {
            $count = $this->routeScannerService->persistSelected($this->selectedRouteCandidates, $this->routeCandidateNames);
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify('Khong the them route vao Menu. Vui long thu lai hoac kiem tra log he thong.', 'error');
            return;
        }

        $this->closeRouteScannerModal();
        $this->notify("Da them {$count} route GET vao menu.", 'success', 'reload');
    }

    public function restoreDefaultMenu(): void
    {
        $this->authorizePermission('admin.menu.restore');

        try {
            $report = $this->importExportService->restoreDefaults();
            $this->importReport = $this->publicImportReport($report);

            if (($report['success'] ?? false) !== true) {
                $this->notify('Khoi phuc menu that bai. Vui long kiem tra report.', 'error');
                return;
            }

            $this->notify("Khoi phuc menu hoan tat: {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.", 'success', 'reload', 100);
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify($exception->getMessage(), 'error');
        }
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

    public function requestBulkDelete(): void
    {
        $this->authorizePermission('admin.menu.delete');
        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu can xoa.', 'warning');
            return;
        }
        $this->showBulkDeleteModal = true;
    }

    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function bulkDelete(): void
    {
        $this->authorizePermission('admin.menu.delete');
        if ($this->selectedMenus === []) {
            $this->showBulkDeleteModal = false;
            $this->notify('Vui long chon menu can xoa.', 'warning');
            return;
        }

        $count = $this->menuService->bulkDelete($this->selectedMenus);
        $this->resetSelection();
        $this->showBulkDeleteModal = false;
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

        $this->validate(['bulkPermission' => 'nullable|exists:permissions,name']);
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
            $path = $this->selectedMenus === []
                ? $this->importExportService->export($this->filters())
                : $this->importExportService->exportSelected($this->selectedMenus);

            return Storage::disk('public')->download($path);
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify('Loi export menu. Vui long kiem tra log.', 'error');

            return null;
        }
    }

    public function exportTemplate()
    {
        $this->authorizePermission('admin.menu.export');

        try {
            return Storage::disk('public')->download($this->importExportService->exportTemplate());
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify('Loi tao file mau menu. Vui long kiem tra log.', 'error');

            return null;
        }
    }

    public function import(): void
    {
        $this->authorizePermission('admin.menu.import');
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240),
            'importMode' => 'required|in:skip_duplicate,update_or_create',
        ]);

        try {
            $report = $this->importExportService->importFromFile($this->importFile->getRealPath(), ['mode' => $this->importMode]);
            $this->importReport = $this->publicImportReport($report);

            if (($report['success'] ?? false) !== true) {
                $this->addError('importFile', 'Import menu co loi. Vui long kiem tra report ben duoi.');
                return;
            }

            $modeLabel = $this->importMode === 'update_or_create' ? 'cap nhat khi trung' : 'bo qua khi trung';
            $this->reset(['showImportModal', 'importFile']);
            $this->importMode = 'skip_duplicate';
            $this->notify("Import menu hoan tat ({$modeLabel}): {$report['success_rows']} dong, {$report['skipped_rows']} bo qua.");
        } catch (\Throwable $exception) {
            report($exception);
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
        return ['search' => $this->search, 'status' => $this->filterStatus];
    }

    private function notify(string $message, string $type = 'success', ?string $action = null, ?int $duration = null): void
    {
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
            'errors' => array_values(array_map(static fn (array $error): array => [
                'row' => $error['row'] ?? null,
                'column' => $error['column'] ?? null,
                'value' => $error['value'] ?? null,
                'reason' => $error['reason'] ?? 'Du lieu khong hop le.',
            ], array_filter($report['errors'] ?? [], 'is_array'))),
        ];
    }
}

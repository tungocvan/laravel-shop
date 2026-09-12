<?php

namespace Modules\Admin\Livewire\Menus;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Admin\Services\MenuImportExportService;
use Modules\Admin\Services\MenuRouteScannerService;
use Modules\Admin\Services\MenuService;
use Modules\Admin\Services\MenuSnapshotCloudSyncService;

class MenuTable extends Component
{
    use WithFileUploads;

    protected MenuService $menuService;

    protected MenuImportExportService $importExportService;

    protected MenuRouteScannerService $routeScannerService;

    protected MenuSnapshotCloudSyncService $snapshotCloudSyncService;

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

    public bool $showSnapshotModal = false;

    public string $snapshotName = 'Default';

    public string $snapshotFilePreview = 'menus-default.json';

    public string $snapshotExportScope = 'all';

    public array $cloudSnapshots = [];

    public array $selectedCloudSnapshotFiles = [];

    public ?string $snapshotListError = null;

    public ?string $selectedSnapshotFile = null;

    public ?string $renamingSnapshotFile = null;

    public string $renameSnapshotName = '';

    public array $routeCandidates = [];

    public array $selectedRouteCandidates = [];

    public array $routeCandidateNames = [];

    public ?string $bulkPermission = null;

    protected $queryString = ['search', 'filterStatus'];

    public function boot(MenuService $menuService, MenuImportExportService $importExportService, MenuRouteScannerService $routeScannerService, MenuSnapshotCloudSyncService $snapshotCloudSyncService): void
    {
        $this->menuService = $menuService;
        $this->importExportService = $importExportService;
        $this->routeScannerService = $routeScannerService;
        $this->snapshotCloudSyncService = $snapshotCloudSyncService;
    }

    protected function rules(): array
    {
        return [
            'importFile' => 'nullable|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240),
            'importMode' => 'required|in:skip_duplicate,update_or_create',
            'snapshotName' => 'required|string|max:80',
            'snapshotExportScope' => 'required|in:all,selected',
            'renameSnapshotName' => 'nullable|string|max:80',
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

    public function updatedSnapshotName(): void
    {
        try {
            $this->snapshotFilePreview = $this->snapshotCloudSyncService->snapshotFileName($this->snapshotName);
            $this->resetValidation('snapshotName');
        } catch (\Throwable) {
            $this->snapshotFilePreview = 'Tên snapshot chưa hợp lệ';
        }
    }

    public function updatedSnapshotExportScope(string $scope): void
    {
        if ($scope === 'selected' && $this->selectedMenus === []) {
            $this->snapshotExportScope = 'all';
        }
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
        $this->selectedMenus = $shouldSelect ? array_values(array_unique(array_merge($selected, $branchIds))) : array_values(array_diff($selected, $branchIds));
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

    public function openSnapshotModal(): void
    {
        $this->authorizePermission('admin.menu.export');
        $this->prepareSnapshotModal();
    }

    public function openSnapshotLibraryModal(): void
    {
        $this->authorizePermission('admin.menu.restore');
        $this->prepareSnapshotModal();
    }

    public function closeSnapshotModal(): void
    {
        $this->showSnapshotModal = false;
        $this->snapshotListError = null;
        $this->selectedCloudSnapshotFiles = [];
        $this->renamingSnapshotFile = null;
        $this->renameSnapshotName = '';
        $this->resetValidation(['snapshotName', 'renameSnapshotName']);
    }

    public function refreshSnapshotLibrary(): void
    {
        $this->authorizePermission('admin.menu.export');
        $this->loadCloudSnapshots();
    }

    public function startRenameSnapshot(string $snapshotFile): void
    {
        $this->authorizePermission('admin.menu.export');
        $this->renamingSnapshotFile = $snapshotFile;
        $this->renameSnapshotName = preg_replace('/\Amenus-?|\.json\z/i', '', $snapshotFile) ?: $snapshotFile;
        $this->resetValidation('renameSnapshotName');
    }

    public function cancelRenameSnapshot(): void
    {
        $this->renamingSnapshotFile = null;
        $this->renameSnapshotName = '';
        $this->resetValidation('renameSnapshotName');
    }

    public function saveRenameSnapshot(): void
    {
        $this->authorizePermission('admin.menu.export');
        $this->validate(['renameSnapshotName' => 'required|string|max:80']);
        if ($this->renamingSnapshotFile === null) {
            return;
        }
        try {
            $oldFile = $this->renamingSnapshotFile;
            $result = $this->snapshotCloudSyncService->renameSnapshot($oldFile, $this->renameSnapshotName);
            $newFile = (string) ($result['name'] ?? '');
            if ($this->selectedSnapshotFile === $oldFile && $newFile !== '') {
                $this->selectedSnapshotFile = $newFile;
            }
            $this->selectedCloudSnapshotFiles = array_values(array_map(static fn (string $file): string => $file === $oldFile && $newFile !== '' ? $newFile : $file, $this->selectedCloudSnapshotFiles));
            $this->cancelRenameSnapshot();
            $this->loadCloudSnapshots();
            $this->notify('Đã đổi tên snapshot trên Google Drive.', 'success');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('renameSnapshotName', $exception->getMessage());
        }
    }

    public function deleteSelectedSnapshots(): void
    {
        $this->authorizePermission('admin.menu.export');
        if ($this->selectedCloudSnapshotFiles === []) {
            $this->notify('Vui lòng chọn ít nhất một snapshot để xóa.', 'warning');

            return;
        }
        try {
            $files = $this->selectedCloudSnapshotFiles;
            $count = $this->snapshotCloudSyncService->deleteSnapshots($files);
            if ($this->selectedSnapshotFile !== null && in_array($this->selectedSnapshotFile, $files, true)) {
                $this->selectedSnapshotFile = null;
            }
            $this->selectedCloudSnapshotFiles = [];
            $this->loadCloudSnapshots();
            $this->notify("Đã xóa {$count} snapshot trên Google Drive.", 'success');
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify($exception->getMessage(), 'error');
        }
    }

    public function openRouteScannerModal(): void
    {
        $this->authorizePermission('admin.menu.view');
        $this->routeCandidates = $this->routeScannerService->candidates();
        $this->selectedRouteCandidates = [];
        $this->routeCandidateNames = collect($this->routeCandidates)->mapWithKeys(fn (array $candidate): array => [(string) $candidate['id'] => (string) $candidate['name']])->all();
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
        $this->selectedRouteCandidates = array_values(array_map(fn (array $candidate): string => (string) $candidate['id'], $this->routeCandidates));
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

    public function syncSnapshotFromGoogleDrive(string $snapshotFile): void
    {
        $this->authorizePermission('admin.menu.restore');
        try {
            $result = $this->snapshotCloudSyncService->pullToLocal($snapshotFile);
            $this->selectedSnapshotFile = (string) ($result['source_file'] ?? $snapshotFile);
            $this->notify("Da dong bo {$this->selectedSnapshotFile} tu Google Drive ve local.", 'success');
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify($exception->getMessage(), 'error');
        }
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
        } $this->notify('Da xoa menu thanh cong.', 'success', 'reload');
    }

    public function toggleStatus($id): void
    {
        $this->authorizePermission('admin.menu.update');
        if (! $this->menuService->toggleStatus($id)) {
            return;
        } $this->notify('Da cap nhat trang thai menu.');
    }

    public function duplicate($id): void
    {
        $this->authorizePermission('admin.menu.create');
        if (! $this->menuService->duplicate($id)) {
            $this->notify('Menu khong ton tai.', 'warning');

            return;
        } $this->notify('Da nhan ban menu thanh cong.', 'success', 'reload');
    }

    public function requestBulkDelete(): void
    {
        $this->authorizePermission('admin.menu.delete');
        if ($this->selectedMenus === []) {
            $this->notify('Vui long chon menu can xoa.', 'warning');

            return;
        } $this->showBulkDeleteModal = true;
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
        } $this->showBulkPermissionsModal = true;
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
            $path = $this->selectedMenus === [] ? $this->importExportService->export($this->filters()) : $this->importExportService->exportSelected($this->selectedMenus);

            return Storage::disk('public')->download($path);
        } catch (\Throwable $exception) {
            report($exception);
            $this->notify('Loi export menu. Vui long kiem tra log.', 'error');

            return null;
        }
    }

    public function exportWithSnapshot(): void
    {
        $this->authorizePermission('admin.menu.export');
        $this->validate(['snapshotName' => 'required|string|max:80', 'snapshotExportScope' => 'required|in:all,selected']);
        if ($this->snapshotExportScope === 'selected' && $this->selectedMenus === []) {
            $this->addError('snapshotExportScope', 'Không còn menu nào được chọn. Vui lòng chọn lại hoặc tạo snapshot toàn bộ.');

            return;
        }
        try {
            $this->snapshotFilePreview = $this->snapshotCloudSyncService->snapshotFileName($this->snapshotName);
            $isSelected = $this->snapshotExportScope === 'selected';
            $synced = $isSelected ? $this->snapshotCloudSyncService->pushSelectedSnapshotBestEffort($this->snapshotName, $this->selectedMenus) : $this->snapshotCloudSyncService->pushFullSnapshotBestEffort($this->snapshotName);
            if (! $synced) {
                $this->notify('Khong the luu snapshot len Google Drive. Vui long kiem tra ket noi.', 'error');

                return;
            }
            $this->selectedSnapshotFile = $this->snapshotFilePreview;
            $this->loadCloudSnapshots();
            $this->notify('Da luu snapshot menu len Google Drive.', 'success');
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('snapshotName', $exception->getMessage());
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
        $this->validate(['importFile' => 'required|file|mimes:xlsx,csv|max:'.config('menu.import.max_file_size', 10240), 'importMode' => 'required|in:skip_duplicate,update_or_create']);
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

        return view('Admin::livewire.menus.menu-table', ['menus' => $this->menuService->rootTree($this->filters()), 'totalMenus' => $stats['totalMenus'], 'activeMenus' => $stats['activeMenus'], 'permissionOptions' => $this->menuService->permissionOptions(), 'snapshotStatus' => $this->snapshotCloudSyncService->status()]);
    }

    private function prepareSnapshotModal(): void
    {
        $this->snapshotName = 'Default';
        $this->snapshotFilePreview = 'menus-default.json';
        $this->snapshotExportScope = $this->selectedMenus === [] ? 'all' : 'selected';
        $this->snapshotListError = null;
        $this->selectedCloudSnapshotFiles = [];
        $this->renamingSnapshotFile = null;
        $this->renameSnapshotName = '';
        $this->resetValidation(['snapshotName', 'snapshotExportScope', 'renameSnapshotName']);
        $this->loadCloudSnapshots();
        $this->showSnapshotModal = true;
    }

    private function loadCloudSnapshots(): void
    {
        try {
            $this->cloudSnapshots = $this->snapshotCloudSyncService->snapshots();
            $available = array_column($this->cloudSnapshots, 'name');
            $this->selectedCloudSnapshotFiles = array_values(array_intersect($this->selectedCloudSnapshotFiles, $available));
            $this->snapshotListError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $this->cloudSnapshots = [];
            $this->snapshotListError = $exception->getMessage();
        }
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
        } if ($duration !== null) {
            $payload['duration'] = $duration;
        } $this->dispatch('notify', ...$payload);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can($permission), 403);
    }

    private function publicImportReport(array $report): array
    {
        return ['success' => (bool) ($report['success'] ?? false), 'total_rows' => (int) ($report['total_rows'] ?? 0), 'success_rows' => (int) ($report['success_rows'] ?? 0), 'error_rows' => (int) ($report['error_rows'] ?? 0), 'skipped_rows' => (int) ($report['skipped_rows'] ?? 0), 'errors' => array_values(array_map(static fn (array $error): array => ['row' => $error['row'] ?? null, 'column' => $error['column'] ?? null, 'value' => $error['value'] ?? null, 'reason' => $error['reason'] ?? 'Du lieu khong hop le.'], array_filter($report['errors'] ?? [], 'is_array')))];
    }
}

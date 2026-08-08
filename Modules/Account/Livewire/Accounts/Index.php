<?php

namespace Modules\Account\Livewire\Accounts;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Account\Services\AccountImportService;
use Modules\Account\Services\AccountService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';

    public string $accountType = '';

    public string $isActive = '';

    public string|int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100, 'All'];

    public array $selectedIds = [];

    public bool $selectAll = false;

    public $importFile = null;

    public ?array $importReport = null;

    protected AccountService $accountService;

    protected AccountImportService $accountImportService;

    public function boot(
        AccountService $accountService,
        AccountImportService $accountImportService
    ): void {
        $this->accountService = $accountService;
        $this->accountImportService = $accountImportService;
    }

    protected function rules(): array
    {
        return [
            'importFile' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            'importFile.required' => 'Vui lòng chọn file Excel để import.',
            'importFile.file' => 'File import không hợp lệ.',
            'importFile.mimes' => 'File import phải là định dạng .xlsx hoặc .xls.',
            'importFile.max' => 'File import không được vượt quá 10MB.',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedAccountType(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedIsActive(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetSelection();
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->selectedIds = [];

            return;
        }

        $this->selectedIds = $this->accountService
            ->getDeletableIds($this->filters())
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    public function updatedSelectedIds(): void
    {
        $this->selectAll = false;
    }

    public function toggleActive(int $id): void
    {
        $this->accountService->toggleActive($id);

        session()->flash('success', 'Đã cập nhật trạng thái tài khoản.');
    }

    public function delete(int $id): void
    {
        $this->accountService->delete($id);

        $this->selectedIds = array_values(array_filter(
            $this->selectedIds,
            fn($selectedId) => (int) $selectedId !== $id
        ));

        $this->selectAll = false;

        session()->flash('success', 'Đã xóa tài khoản.');
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            session()->flash('error', 'Vui lòng chọn ít nhất một tài khoản.');

            return;
        }

        $this->accountService->bulkDelete($this->selectedIds);

        $this->resetSelection();

        session()->flash('success', 'Đã xóa các tài khoản đã chọn.');
    }

    public function import(): void
    {
        $this->importAccounts();
    }

    public function importAccounts(): void
    {
        $this->validate();

        try {
            $path = $this->importFile->store('imports/account', 'local');

            $this->importReport = $this->accountImportService->import(
                storage_path('app/' . $path)
            );

            $this->reset('importFile');
            $this->resetSelection();
            $this->resetPage();

            if ($this->importReport['success'] ?? false) {
                session()->flash('success', 'Import tài khoản thành công.');
                return;
            }

            session()->flash('error', 'Import thất bại. Vui lòng kiểm tra báo cáo lỗi.');
        } catch (\Throwable $e) {
            $this->importReport = [
                'success' => false,
                'total_rows' => 0,
                'success_rows' => 0,
                'error_rows' => 1,
                'errors' => [
                    [
                        'sheet' => 'system',
                        'row' => '-',
                        'column' => '-',
                        'reason' => $e->getMessage(),
                    ],
                ],
            ];

            session()->flash('error', 'Import thất bại: ' . $e->getMessage());
        }
    }

    public function clearImportReport(): void
    {
        $this->importReport = null;

        $this->reset('importFile');
        $this->resetValidation('importFile');
    }

    public function export(): BinaryFileResponse
    {
        return $this->accountService->export($this->filters());
    }

    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'account_type' => $this->accountType,
            'is_active' => $this->isActive,
            'per_page' => $this->perPage,
        ];
    }

    protected function resetSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function render(): View
    {
        return view('Account::livewire.accounts.index', [
            'accounts' => $this->accountService->paginateForAdmin($this->filters()),
        ]);
    }
}

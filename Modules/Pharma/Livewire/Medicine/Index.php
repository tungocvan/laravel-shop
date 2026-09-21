<?php

namespace Modules\Pharma\Livewire\Medicine;

use Exception;
use Livewire\Component;
use LogicException;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineService;

class Index extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public int $page = 1;

    public int $perPage = 10;

    public string $filterCircularGroup = '';

    public string $filterSpecialControl = '';

    public string $filterProfileStatus = '';

    public string $filterHssp = '';

    public string $filterSupplier = '';

    public string $filterDeletable = '';

    public array $selectedIds = [];

    public bool $selectPage = false;

    public ?int $confirmingDeleteId = null;

    public ?string $confirmingDeleteName = null;

    public ?string $deleteResultType = null;

    public ?string $deleteResultMessage = null;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount(): void
    {
        $this->authorizePharmaView();
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterCircularGroup(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterSpecialControl(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterHssp(): void
    {
        if (! in_array($this->filterHssp, ['', 'with', 'without'], true)) {
            $this->filterHssp = '';
        }

        $this->resetWorkspacePage();
    }

    public function updatedFilterDeletable(): void
    {
        if (! in_array($this->filterDeletable, ['', 'yes', 'no'], true)) {
            $this->filterDeletable = '';
        }
        $this->resetWorkspacePage();
    }

    public function updatedFilterSupplier(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterProfileStatus(): void
    {
        $this->filterProfileStatus = array_key_exists($this->filterProfileStatus, $this->profileStatusOptions())
            ? $this->filterProfileStatus
            : '';
        $this->resetWorkspacePage();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetWorkspacePage();
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selectedIds = $value ? $this->currentPageIds() : [];
    }

    public function updatedSelectedIds(): void
    {
        $pageIds = $this->currentPageIds();
        $this->selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function hasActiveSelectFilters(): bool
    {
        return $this->filterCircularGroup !== ''
            || $this->filterSpecialControl !== ''
            || $this->filterProfileStatus !== ''
            || $this->filterHssp !== ''
            || $this->filterSupplier !== ''
            || $this->filterDeletable !== ''
            || $this->perPage !== 10;
    }

    public function resetFilters(): void
    {
        $this->reset(['filterCircularGroup', 'filterSpecialControl', 'filterProfileStatus', 'filterHssp', 'filterSupplier', 'filterDeletable']);
        $this->perPage = 10;
        $this->page = 1;
        $this->clearSelection();
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->clearSelection();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizePharmaDelete();
        $medicine = Medicine::query()->withCount('profiles')->findOrFail($id);

        if ($medicine->profiles_count > 0) {
            $this->showDeleteResult('error', 'Không thể xóa thuốc vì đã có Hồ sơ sản phẩm (HSSP). Hãy xóa HSSP trước nếu thực sự muốn xóa Medicine.');

            return;
        }

        $this->confirmingDeleteId = $id;
        $this->confirmingDeleteName = $medicine->name;
        $this->deleteResultType = null;
        $this->deleteResultMessage = null;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteName = null;
    }

    public function closeDeleteResult(): void
    {
        $this->deleteResultType = null;
        $this->deleteResultMessage = null;
    }

    public function deleteConfirmed(MedicineService $medicineService): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $this->deleteMedicine($medicineService, $this->confirmingDeleteId);
    }

    public function deleteMedicine(MedicineService $medicineService, int $id): void
    {
        $this->authorizePharmaDelete();

        try {
            $medicineService->delete($id);
            $this->clearSelection();
            $this->showDeleteResult('success', 'Đã xóa thuốc khỏi Medicine Master thành công.');
        } catch (LogicException $exception) {
            $this->showDeleteResult('error', $exception->getMessage());
        } catch (Exception $exception) {
            report($exception);
            $this->showDeleteResult('error', 'Không thể xóa thuốc do lỗi hệ thống. Vui lòng kiểm tra log.');
        } finally {
            $this->cancelDelete();
        }
    }

    public function deleteSelected(MedicineService $medicineService): void
    {
        $this->authorizePharmaDelete();
        $ids = array_values(array_intersect(array_map('strval', $this->selectedIds), $this->currentPageIds()));

        if ($ids === []) {
            $this->clearSelection();

            return;
        }

        $deleted = 0;
        $protected = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $medicineService->delete((int) $id);
                $deleted++;
            } catch (LogicException) {
                $protected++;
            } catch (Exception $exception) {
                report($exception);
                $failed++;
            }
        }

        $this->clearSelection();

        if ($deleted > 0) {
            session()->flash('success', sprintf('Đã xóa %d thuốc khỏi Medicine Master.', $deleted));
        }

        if ($protected > 0 || $failed > 0) {
            session()->flash('error', sprintf(
                '%d thuốc không thể xóa do đã có HSSP/kết quả lựa chọn nhà thầu tham chiếu; %d thuốc gặp lỗi hệ thống.',
                $protected,
                $failed,
            ));
        }
    }

    public function render(MedicineService $medicineService)
    {
        $this->perPage = $this->normalizePerPage($this->perPage);
        $medicines = $this->paginated($medicineService);

        if ($medicines->lastPage() > 0 && $this->page > $medicines->lastPage()) {
            $this->page = $medicines->lastPage();
            $this->clearSelection();
            $medicines = $this->paginated($medicineService);
        }

        return view('Pharma::livewire.medicine.index', [
            'medicines' => $medicines,
            'circularGroups' => $medicineService->getUniqueCircularGroups(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'profileStatusOptions' => $this->profileStatusOptions(),
            'supplierOptions' => $medicineService->getSupplierOptions(),
        ]);
    }

    private function paginated(MedicineService $service)
    {
        return $service->getPaginatedMedicines(
            $this->search,
            $this->perPage,
            $this->page,
            $this->filterCircularGroup,
            $this->filterSpecialControl,
            $this->filterProfileStatus ?: null,
            $this->filterHssp ?: null,
            $this->filterSupplier !== '' ? (int) $this->filterSupplier : null,
            $this->filterDeletable ?: null,
        );
    }

    private function currentPageIds(): array
    {
        return collect($this->paginated(app(MedicineService::class))->items())
            ->map(static fn ($medicine): string => (string) $medicine->id)
            ->values()
            ->all();
    }

    private function resetWorkspacePage(): void
    {
        $this->page = 1;
        $this->clearSelection();
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
    }

    private function showDeleteResult(string $type, string $message): void
    {
        $this->deleteResultType = $type;
        $this->deleteResultMessage = $message;
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function profileStatusOptions(): array
    {
        return [
            '' => 'Tất cả chất lượng master',
            Medicine::PROFILE_INCOMPLETE => 'Thiếu dữ liệu',
            Medicine::PROFILE_NEEDS_REVIEW => 'Cần rà soát',
            Medicine::PROFILE_COMPLETE => 'Đầy đủ',
            Medicine::PROFILE_VERIFIED => 'Đã xác minh',
        ];
    }
}

<?php

namespace Modules\Admission\Livewire\Admin\Applications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Admission\Services\AdmissionApplicationAdminService;

class Index extends Component
{
    use WithPagination;

    private const PER_PAGE_OPTIONS = [5, 10, 20, 50];

    public $search = '';
    public $filterStatus = '';
    public $filterClass = '';
    public $perPage = 10;
    public $selected = [];
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterClass' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updated($field): void
    {
        if ($field === 'perPage') {
            $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true)
                ? (int) $this->perPage
                : 10;
        }

        if (in_array($field, ['search', 'filterStatus', 'filterClass', 'perPage'], true)) {
            $this->resetPage();
            $this->resetSelection();
        }
    }

    protected function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if (! $this->adminCan('delete_admission') && ! $this->adminCan('download_admission_documents')) {
            $this->resetSelection();
            return;
        }

        $this->selected = $value
            ? $this->applications->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    public function getApplicationsProperty()
    {
        return app(AdmissionApplicationAdminService::class)
            ->paginate($this->filters(), (int) $this->perPage);
    }

    public function approve($id): void
    {
        $adminId = $this->authorizeAdmin('approve_admission');
        app(AdmissionApplicationAdminService::class)->approve((int) $id, $adminId);
    }

    public function reject($id): void
    {
        $adminId = $this->authorizeAdmin('reject_admission');
        app(AdmissionApplicationAdminService::class)->reject((int) $id, $adminId);
    }

    public function deleteSelected(): void
    {
        $this->authorizeAdmin('delete_admission');
        app(AdmissionApplicationAdminService::class)->deleteMany($this->selected);
        $this->resetSelection();
        $this->resetPage();
    }

    public function deleteAll(): void
    {
        $this->authorizeAdmin('delete_admission');
        app(AdmissionApplicationAdminService::class)->deleteAllAndResetIncrement();
        $this->resetSelection();
        $this->resetPage();
    }

    public function delete($id): void
    {
        $this->authorizeAdmin('delete_admission');
        app(AdmissionApplicationAdminService::class)->deleteMany([(int) $id]);
        $this->resetSelection();
    }

    public function generateSelectedDocuments(): void
    {
        $this->authorizeAdmin('download_admission_documents');

        $queued = app(AdmissionApplicationAdminService::class)
            ->queueDocumentsForIds($this->selected);

        session()->flash(
            'success',
            $queued > 0
                ? "Đã đưa {$queued} hồ sơ đã chọn còn thiếu file vào hàng đợi tạo tài liệu."
                : 'Các hồ sơ đã chọn không có hồ sơ Đã duyệt nào thiếu file cần tạo.'
        );
    }

    public function generateDocuments(): void
    {
        $this->authorizeAdmin('download_admission_documents');

        if ($this->filterStatus !== 'approved') {
            $this->addError('documents', 'Hãy chọn trạng thái Đã duyệt trước khi tạo file hàng loạt.');
            return;
        }

        $queued = app(AdmissionApplicationAdminService::class)
            ->queueDocumentsForFilters($this->filters());

        session()->flash(
            'success',
            $queued > 0
                ? "Đã đưa {$queued} hồ sơ thiếu file vào hàng đợi tạo tài liệu."
                : 'Không có hồ sơ đã duyệt nào thiếu file cần tạo.'
        );
    }

    public function export()
    {
        $this->authorizeAdmin('export_admission');

        return app(AdmissionApplicationAdminService::class)->downloadExport($this->filters());
    }

    public function render()
    {
        return view('Admission::livewire.admin.applications.index', [
            'applications' => $this->applications,
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->filterStatus,
            'class' => $this->filterClass,
        ];
    }

    private function authorizeAdmin(string $permission): int
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->can($permission), 403);

        return (int) $admin->getAuthIdentifier();
    }

    private function adminCan(string $permission): bool
    {
        $admin = Auth::guard('admin')->user();

        return (bool) ($admin && $admin->can($permission));
    }
}

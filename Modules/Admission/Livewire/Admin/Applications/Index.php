<?php

namespace Modules\Admission\Livewire\Admin\Applications;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
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

    public bool $generateDocx = true;

    public bool $generatePdf = false;

    public ?string $documentBatchId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterClass' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(): void
    {
        $this->documentBatchId = session('admission_document_batch_id');
    }

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

    public function getDocumentBatchProperty()
    {
        return $this->documentBatchId ? Bus::findBatch($this->documentBatchId) : null;
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

        if (! $this->validateDocumentFormats()) {
            return;
        }

        $batch = app(AdmissionApplicationAdminService::class)
            ->queueDocumentsForIds($this->selected, $this->generateDocx, $this->generatePdf);

        $this->rememberDocumentBatch($batch?->id);

        session()->flash(
            'success',
            $batch
                ? "Đã tạo batch {$batch->totalJobs} hồ sơ trên queue admission-documents."
                : 'Các hồ sơ đã chọn không có hồ sơ Đã duyệt nào thiếu định dạng file đã chọn.'
        );
    }

    public function generateDocuments(): void
    {
        $this->authorizeAdmin('download_admission_documents');

        if ($this->filterStatus !== 'approved') {
            $this->addError('documents', 'Hãy chọn trạng thái Đã duyệt trước khi tạo file hàng loạt.');

            return;
        }

        if (! $this->validateDocumentFormats()) {
            return;
        }

        $batch = app(AdmissionApplicationAdminService::class)
            ->queueDocumentsForFilters($this->filters(), $this->generateDocx, $this->generatePdf);

        $this->rememberDocumentBatch($batch?->id);

        session()->flash(
            'success',
            $batch
                ? "Đã tạo batch {$batch->totalJobs} hồ sơ trên queue admission-documents."
                : 'Không có hồ sơ đã duyệt nào thiếu định dạng file đã chọn.'
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
            'documentBatch' => $this->documentBatch,
        ]);
    }

    private function validateDocumentFormats(): bool
    {
        $this->resetErrorBag('documents');

        if (! $this->generateDocx && ! $this->generatePdf) {
            $this->addError('documents', 'Chọn ít nhất một định dạng: DOCX hoặc PDF.');

            return false;
        }

        return true;
    }

    private function rememberDocumentBatch(?string $batchId): void
    {
        if (! $batchId) {
            return;
        }

        $this->documentBatchId = $batchId;
        session()->put('admission_document_batch_id', $batchId);
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

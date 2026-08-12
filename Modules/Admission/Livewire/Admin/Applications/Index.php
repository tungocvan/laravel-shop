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
        if (! $this->adminCan('delete_admission')) {
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
    }

    public function delete($id): void
    {
        $this->authorizeAdmin('delete_admission');

        app(AdmissionApplicationAdminService::class)->deleteMany([(int) $id]);
        $this->resetSelection();
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

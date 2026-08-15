<?php

namespace Modules\Administrative\Livewire\Submissions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Administrative\Services\SubmissionService;

class SubmissionTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $procedure_id = '';

    public string $date_from = '';

    public string $date_to = '';

    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public array $selectedIds = [];

    public bool $selectAll = false;

    public bool $confirmingDelete = false;

    public bool $confirmingDeleteAll = false;

    public function mount(): void
    {
        $this->authorizePermission('administrative.submission.view');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'procedure_id', 'date_from', 'date_to', 'perPage'], true)) {
            if ($property === 'perPage' && ! in_array($this->perPage, $this->perPageOptions, true)) {
                $this->perPage = 10;
            }
            $this->reset(['selectedIds', 'selectAll']);
            $this->resetPage();
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'procedure_id', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function toggleSelectPage(array $ids): void
    {
        $this->authorizeAnyPermission([
            'administrative.submission.delete',
            'administrative.submission.import_export',
        ]);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $this->selectAll = ! $this->selectAll;
        $this->selectedIds = $this->selectAll
            ? array_values(array_unique(array_merge($this->selectedIds, $ids)))
            : array_values(array_diff($this->selectedIds, $ids));
    }

    public function requestDelete(): void
    {
        $this->authorizePermission('administrative.submission.delete');
        if ($this->selectedIds === []) {
            $this->dispatch('notify', content: 'Vui lòng chọn ít nhất một hồ sơ.', type: 'warning');

            return;
        }
        $this->confirmingDelete = true;
    }

    public function deleteSelected(SubmissionService $service): void
    {
        $this->authorizePermission('administrative.submission.delete');
        $count = $service->softDeleteMany($this->selectedIds, (int) Auth::guard('admin')->id());
        $this->reset(['selectedIds', 'selectAll', 'confirmingDelete']);
        $this->resetPage();
        $this->dispatch('notify', content: "Đã lưu trữ {$count} hồ sơ.", type: 'success');
    }

    public function requestDeleteAll(): void
    {
        $this->authorizePermission('administrative.submission.delete');
        $this->confirmingDeleteAll = true;
    }

    public function deleteAll(SubmissionService $service): void
    {
        $this->authorizePermission('administrative.submission.delete');
        $count = $service->softDeleteAll((int) Auth::guard('admin')->id());
        $this->reset(['selectedIds', 'selectAll', 'confirmingDelete', 'confirmingDeleteAll']);
        $this->resetPage();
        $this->dispatch('notify', content: "Đã lưu trữ toàn bộ {$count} hồ sơ.", type: 'success');
    }

    public function render(SubmissionService $service)
    {
        $this->authorizePermission('administrative.submission.view');
        $filters = [
            'search' => $this->search,
            'status' => $this->status,
            'procedure_id' => $this->procedure_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        return view('Administrative::livewire.submissions.submission-table', [
            'submissions' => $service->listForAdmin($filters, $this->perPage),
            'stats' => $service->adminStats(),
            'procedures' => $service->procedureOptions(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }

    private function authorizeAnyPermission(array $permissions): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);
        abort_unless(Gate::forUser($user)->any($permissions), 403);
    }
}

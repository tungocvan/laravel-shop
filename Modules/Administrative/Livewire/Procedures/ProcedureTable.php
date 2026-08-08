<?php

namespace Modules\Administrative\Livewire\Procedures;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Administrative\Services\ProcedureService;

class ProcedureTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string|int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100, 'All'];

    public ?int $pendingArchiveId = null;

    public function mount(): void
    {
        $this->authorizePermission('administrative.procedure.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, $this->perPageOptions, true)) {
            $this->perPage = 10;
        }
        $this->resetPage();
    }

    public function setActive(int $id, bool $active, ProcedureService $service): void
    {
        $this->authorizePermission('administrative.procedure.update');
        $service->setActive($id, $active);
        $this->dispatch('notify', content: 'Đã cập nhật trạng thái thủ tục.', type: 'success');
    }

    public function requestArchive(int $id): void
    {
        $this->authorizePermission('administrative.procedure.archive');
        $this->pendingArchiveId = $id;
    }

    public function archive(ProcedureService $service): void
    {
        $this->authorizePermission('administrative.procedure.archive');
        if ($this->pendingArchiveId === null) {
            return;
        }
        $service->archive($this->pendingArchiveId);
        $this->pendingArchiveId = null;
        $this->dispatch('notify', content: 'Đã lưu trữ thủ tục.', type: 'success');
    }

    public function render(ProcedureService $service)
    {
        $this->authorizePermission('administrative.procedure.view');

        return view('Administrative::livewire.procedures.procedure-table', [
            'procedures' => $service->listForAdmin([
                'search' => $this->search,
                'status' => $this->status,
            ], $this->perPage),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}

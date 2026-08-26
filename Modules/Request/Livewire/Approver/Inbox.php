<?php

namespace Modules\Request\Livewire\Approver;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Models\RequestTask;

class Inbox extends Component
{
    use WithPagination;

    public string $search = '';

    public string $view = 'pending';

    public string $decision = 'all';

    public int $perPage = 25;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'view', 'decision', 'perPage'], true)) {
            $this->search = mb_substr(trim($this->search), 0, 100);
            $this->view = in_array($this->view, ['pending', 'processed', 'all'], true) ? $this->view : 'pending';
            $this->decision = in_array($this->decision, ['all', 'approved', 'rejected', 'returned'], true) ? $this->decision : 'all';
            if ($this->view !== 'processed') {
                $this->decision = 'all';
            }
            $this->perPage = in_array($this->perPage, config('request.settings.page_sizes', [10, 25, 50, 100]), true) ? $this->perPage : 25;
            $this->resetPage();
        }
    }

    public function selectView(string $view): void
    {
        $this->view = in_array($view, ['pending', 'processed', 'all'], true) ? $view : 'pending';
        $this->decision = 'all';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->view = 'pending';
        $this->decision = 'all';
        $this->perPage = 25;
        $this->resetPage();
    }

    public function render(ApproverInboxQuery $query)
    {
        Gate::authorize('viewAny', RequestTask::class);
        $userId = (int) auth('admin')->id();

        return view('Request::livewire.approver.inbox', [
            'tasks' => $query->paginate($userId, $this->search, $this->perPage, $this->view, $this->decision),
            'workload' => $query->workloadSummary($userId),
            'processedSummary' => $query->processedSummary($userId),
        ]);
    }
}

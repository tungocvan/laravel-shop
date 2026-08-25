<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;

class MyRequests extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $workspace = 'all';

    public int $perPage = 25;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'workspace', 'perPage'], true)) {
            $this->search = mb_substr(trim($this->search), 0, 100);
            $this->status = in_array($this->status, array_column(RequestStatus::cases(), 'value'), true) ? $this->status : '';
            $this->workspace = in_array($this->workspace, ['all', 'draft', 'processing', 'returned', 'completed'], true) ? $this->workspace : 'all';
            $this->perPage = in_array($this->perPage, config('request.settings.page_sizes', [10, 25, 50, 100]), true) ? $this->perPage : 25;
            $this->resetPage();
        }
    }

    public function selectWorkspace(string $workspace): void
    {
        $this->workspace = in_array($workspace, ['all', 'draft', 'processing', 'returned', 'completed'], true) ? $workspace : 'all';
        $this->status = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status');
        $this->workspace = 'all';
        $this->resetPage();
    }

    public function render(MyRequestsQuery $query)
    {
        $user = auth('admin')->user();
        Gate::forUser($user)->authorize('viewAny', InternalRequest::class);

        return view('Request::livewire.requester.my-requests', [
            'requests' => $query->paginate((int) $user->getAuthIdentifier(), $this->search, $this->status, $this->perPage, $this->workspace),
            'workspaceCounts' => $query->workspaceCounts((int) $user->getAuthIdentifier()),
            'statuses' => RequestStatus::cases(),
            'pageSizes' => config('request.settings.page_sizes', [10, 25, 50, 100]),
        ]);
    }
}

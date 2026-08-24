<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Queries\RequestCatalogQuery;
use Modules\Request\Models\InternalRequest;

class Catalog extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $groupId = null;

    public int $perPage = 25;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'groupId', 'perPage'], true)) {
            $this->search = mb_substr(trim($this->search), 0, 100);
            $this->perPage = in_array($this->perPage, config('request.settings.page_sizes', [10, 25, 50, 100]), true) ? $this->perPage : 25;
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'groupId');
        $this->resetPage();
    }

    public function render(RequestCatalogQuery $query)
    {
        Gate::authorize('create', InternalRequest::class);
        $userId = (int) auth('admin')->id();

        return view('Request::livewire.requester.catalog', [
            'types' => $query->paginate($userId, $this->search, $this->groupId, $this->perPage),
            'groups' => $query->groupOptions($userId),
            'pageSizes' => config('request.settings.page_sizes', [10, 25, 50, 100]),
        ]);
    }
}

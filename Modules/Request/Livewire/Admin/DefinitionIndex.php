<?php

namespace Modules\Request\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

class DefinitionIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $groupCode = '';

    public string $groupName = '';

    public ?int $requestGroupId = null;

    public string $typeCode = '';

    public string $typeName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createGroup(CreateRequestGroup $service): void
    {
        Gate::authorize('create', RequestGroup::class);
        $data = $this->validate(['groupCode' => ['required', 'alpha_dash', 'max:80', 'unique:request_groups,code'], 'groupName' => ['required', 'string', 'max:160']]);
        $service->handle(['code' => strtoupper($data['groupCode']), 'name' => $data['groupName']], (int) auth('admin')->id());
        $this->reset('groupCode', 'groupName');
        session()->flash('request_success', __('Request::request.saved'));
    }

    public function createType(CreateRequestType $service): void
    {
        Gate::authorize('create', RequestType::class);
        $data = $this->validate(['requestGroupId' => ['required', 'integer', 'exists:request_groups,id'], 'typeCode' => ['required', 'alpha_dash', 'max:80', 'unique:request_types,code'], 'typeName' => ['required', 'string', 'max:180']]);
        $service->handle(['request_group_id' => $data['requestGroupId'], 'code' => strtoupper($data['typeCode']), 'name' => $data['typeName']], (int) auth('admin')->id());
        $this->reset('requestGroupId', 'typeCode', 'typeName');
        session()->flash('request_success', __('Request::request.saved'));
    }

    public function render()
    {
        Gate::authorize('viewAny', RequestType::class);

        return view('Request::livewire.admin.definition-index', [
            'groups' => RequestGroup::query()->whereNull('archived_at')->orderBy('sort_order')->limit(100)->get(['id', 'code', 'name']),
            'types' => RequestType::query()->with('group:id,name')->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('code', 'like', '%'.trim($this->search).'%')->orWhere('name', 'like', '%'.trim($this->search).'%')))->orderBy('sort_order')->paginate(25),
        ]);
    }
}

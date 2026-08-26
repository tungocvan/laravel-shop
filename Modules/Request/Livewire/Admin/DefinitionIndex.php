<?php

namespace Modules\Request\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\DeleteUnpublishedRequestType;
use Modules\Request\Application\Services\DuplicateRequestType;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

class DefinitionIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $groupCode = '';

    public string $groupName = '';

    public ?int $requestGroupId = null;

    public string $typeCode = '';

    public string $typeName = '';

    public ?string $duplicateSourcePublicId = null;
    public ?int $duplicateGroupId = null;
    public string $duplicateCode = '';
    public string $duplicateName = '';
    public bool $duplicateAudience = true;

    public function updatedSearch(): void
    {
        $this->search = mb_substr(trim($this->search), 0, 100);
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $allowed = array_map(fn (RequestTypeStatus $status): string => $status->value, RequestTypeStatus::cases());
        $this->status = in_array($this->status, $allowed, true) ? $this->status : '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    public function createGroup(CreateRequestGroup $service): void
    {
        Gate::authorize('create', RequestGroup::class);
        $data = $this->validate([
            'groupCode' => ['required', 'alpha_dash', 'max:80', 'unique:request_groups,code'],
            'groupName' => ['required', 'string', 'max:160'],
        ]);

        $service->handle([
            'code' => strtoupper($data['groupCode']),
            'name' => $data['groupName'],
        ], (int) auth('admin')->id());

        $this->reset('groupCode', 'groupName');
        session()->flash('request_success', __('Request::request.saved'));
    }

    public function createType(CreateRequestType $service): void
    {
        Gate::authorize('create', RequestType::class);
        $data = $this->validate([
            'requestGroupId' => ['required', 'integer', 'exists:request_groups,id'],
            'typeCode' => ['required', 'alpha_dash', 'max:80', 'unique:request_types,code'],
            'typeName' => ['required', 'string', 'max:180'],
        ]);

        $service->handle([
            'request_group_id' => $data['requestGroupId'],
            'code' => strtoupper($data['typeCode']),
            'name' => $data['typeName'],
        ], (int) auth('admin')->id());

        $this->reset('requestGroupId', 'typeCode', 'typeName');
        session()->flash('request_success', __('Request::request.saved'));
    }

    public function prepareDuplicate(string $publicId): void
    {
        $this->resetValidation();
        $type = RequestType::query()->where('public_id', $publicId)->firstOrFail();
        Gate::authorize('update', $type);
        Gate::authorize('create', RequestType::class);
        $this->duplicateSourcePublicId = $type->public_id;
        $this->duplicateGroupId = $type->request_group_id;
        $this->duplicateCode = $type->code.'_COPY';
        $this->duplicateName = $type->name.' (Bản sao)';
        $this->duplicateAudience = Gate::allows('manageAudience', $type);
    }

    public function duplicateType(DuplicateRequestType $service): void
    {
        $source = RequestType::query()->where('public_id', $this->duplicateSourcePublicId)->firstOrFail();
        Gate::authorize('update', $source);
        Gate::authorize('create', RequestType::class);
        if ($this->duplicateAudience) {
            Gate::authorize('manageAudience', $source);
        }
        $data = $this->validate([
            'duplicateGroupId' => ['required', 'integer', 'exists:request_groups,id'],
            'duplicateCode' => ['required', 'alpha_dash', 'max:80', 'unique:request_types,code'],
            'duplicateName' => ['required', 'string', 'max:180'],
            'duplicateAudience' => ['boolean'],
        ]);
        $type = $service->handle($source, [
            'request_group_id' => $data['duplicateGroupId'], 'code' => strtoupper($data['duplicateCode']), 'name' => $data['duplicateName'],
        ], (int) auth('admin')->id(), (bool) $data['duplicateAudience']);
        session()->flash('request_success', 'Đã nhân bản thành loại đề nghị v1 bản nháp.');
        $this->redirectRoute('request.admin.types.designer', ['typePublicId' => $type->public_id]);
    }

    public function deleteType(string $publicId, DeleteUnpublishedRequestType $service): void
    {
        $type = RequestType::query()->where('public_id', $publicId)->firstOrFail();
        Gate::authorize('delete', $type);
        $service->handle($type, (int) auth('admin')->id());
        session()->flash('request_success', 'Đã xóa loại đề nghị chưa phát hành.');
    }

    public function render()
    {
        Gate::authorize('viewAny', RequestType::class);

        return view('Request::livewire.admin.definition-index', [
            'groups' => RequestGroup::query()
                ->whereNull('archived_at')
                ->orderBy('sort_order')
                ->limit(100)
                ->get(['id', 'code', 'name']),
            'types' => RequestType::query()
                ->with(['group:id,name', 'activeDraft:id,version_number', 'currentPublishedVersion:id,version_number'])
                ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($nested) => $nested
                    ->where('code', 'like', '%'.trim($this->search).'%')
                    ->orWhere('name', 'like', '%'.trim($this->search).'%')))
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(25),
        ]);
    }
}

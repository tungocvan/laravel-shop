<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\RequestCatalogQuery;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Models\InternalRequest;

class CreateDraft extends Component
{
    public string $typePublicId;

    public string $idempotencyKey;

    public function mount(string $typePublicId, RequestCatalogQuery $query): void
    {
        Gate::authorize('create', InternalRequest::class);
        $this->typePublicId = $typePublicId;
        $query->findEligible($typePublicId, (int) auth('admin')->id(), AudienceCapability::Create);
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function create(CreateInternalRequest $service, RequestCatalogQuery $query): void
    {
        Gate::authorize('create', InternalRequest::class);
        $type = $query->findEligible($this->typePublicId, (int) auth('admin')->id(), AudienceCapability::Create);
        $request = $service->handle($type, (int) auth('admin')->id(), $this->idempotencyKey);
        $this->redirectRoute('request.show', $request->public_id);
    }

    public function render(RequestCatalogQuery $query)
    {
        $type = $query->findEligible($this->typePublicId, (int) auth('admin')->id(), AudienceCapability::Create);

        return view('Request::livewire.requester.create-draft', ['type' => $type]);
    }
}

<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\RequestCatalogQuery;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;
use Modules\Request\Models\InternalRequest;

class CreateDraft extends Component
{
    use InteractsWithRequestAuthorization;

    public string $typePublicId;

    public string $idempotencyKey;

    public function mount(string $typePublicId, RequestCatalogQuery $query, RequestAuthorizationContext $context): void
    {
        $this->initializeRequestAuthorization($context);
        $user = $this->requestActor($context);
        Gate::forUser($user)->authorize('create', InternalRequest::class);
        $this->typePublicId = $typePublicId;
        $query->findEligible($typePublicId, (int) $user->getAuthIdentifier(), AudienceCapability::Create);
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function create(CreateInternalRequest $service, RequestCatalogQuery $query, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        Gate::forUser($user)->authorize('create', InternalRequest::class);
        $type = $query->findEligible($this->typePublicId, (int) $user->getAuthIdentifier(), AudienceCapability::Create);
        $request = $service->handle($type, (int) $user->getAuthIdentifier(), $this->idempotencyKey);
        $this->redirectRoute($this->requestRouteName('show'), ['requestPublicId' => $request->public_id]);
    }

    public function render(RequestCatalogQuery $query, RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $type = $query->findEligible($this->typePublicId, (int) $user->getAuthIdentifier(), AudienceCapability::Create);

        return view('Request::livewire.requester.create-draft', [
            'type' => $type,
            'catalogRouteName' => $this->requestRouteName('catalog'),
        ]);
    }
}

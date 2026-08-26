<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Application\Services\ResubmitInternalRequest;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Domain\Forms\FormDefaultValueResolver;
use Modules\Request\Domain\Forms\VisibilityRuleEvaluator;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;

class RequestDetail extends Component
{
    use InteractsWithRequestAuthorization;

    public string $requestPublicId;

    public array $values = [];

    public int $lockVersion;

    public string $saveKey;

    public string $cancelKey;

    public string $submitKey;

    public bool $confirmingCancel = false;

    public bool $confirmingSubmit = false;

    public function mount(string $requestPublicId, MyRequestsQuery $query, FormDefaultValueResolver $defaults, RequestAuthorizationContext $context): void
    {
        $this->initializeRequestAuthorization($context);
        $user = $this->requestActor($context);
        $this->requestPublicId = $requestPublicId;
        $request = $query->findVisible($requestPublicId, $user);
        Gate::forUser($user)->authorize('view', $request);
        $this->values = $request->latestPayloadRevision
            ? (array) $request->latestPayloadRevision->payload_json
            : $defaults->values((array) $request->typeVersion->form_schema_json);
        $this->lockVersion = $request->lock_version;
        $this->saveKey = (string) Str::uuid();
        $this->cancelKey = (string) Str::uuid();
        $this->submitKey = (string) Str::uuid();
    }

    public function submit(MyRequestsQuery $query, SubmitInternalRequest $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('submit', $request);
        $service->handle($request, (int) $user->getAuthIdentifier(), $this->lockVersion, $this->submitKey, $this->values);
        session()->flash('request_success', __('Request::request.request_submitted'));
        $this->redirectRoute($this->requestRouteName('show'), ['requestPublicId' => $request->public_id]);
    }

    public function resubmit(MyRequestsQuery $query, ResubmitInternalRequest $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('submit', $request);
        $service->handle($request, $this->values, (int) $user->getAuthIdentifier(), $this->lockVersion, $this->submitKey);
        session()->flash('request_success', __('Request::request.request_resubmitted'));
        $this->redirectRoute($this->requestRouteName('show'), ['requestPublicId' => $request->public_id]);
    }

    public function save(MyRequestsQuery $query, SaveRequestDraft $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('update', $request);
        $service->handle($request, $this->values, (int) $user->getAuthIdentifier(), $this->lockVersion, $this->saveKey);
        $this->lockVersion = $request->refresh()->lock_version;
        $this->saveKey = (string) Str::uuid();
        session()->flash('request_success', __('Request::request.draft_saved'));
    }

    public function cancel(MyRequestsQuery $query, CancelInternalRequest $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('cancel', $request);
        $service->handle($request, (int) $user->getAuthIdentifier(), $this->lockVersion, $this->cancelKey);
        $this->redirectRoute($this->requestRouteName('mine'));
    }

    #[On('request-version-changed')]
    public function updateRequestVersion(int $version): void
    {
        $this->lockVersion = $version;
    }

    #[On('request-attachment-created')]
    public function addAttachmentReference(string $attachmentPublicId, string $fieldKey, int $version): void
    {
        $current = is_array($this->values[$fieldKey] ?? null) ? $this->values[$fieldKey] : [];
        $this->values[$fieldKey] = array_values(array_unique([...$current, $attachmentPublicId]));
        $this->lockVersion = $version;
    }

    public function render(MyRequestsQuery $query, VisibilityRuleEvaluator $visibility, RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('view', $request);

        $schema = (array) $request->typeVersion->form_schema_json;
        $schema['sections'] = collect((array) ($schema['sections'] ?? []))->map(function (array $section) use ($visibility): array {
            $section['fields'] = collect((array) ($section['fields'] ?? []))->filter(fn (array $field): bool => $visibility->visible($field['visible_when'] ?? null, $this->values))->values()->all();

            return $section;
        })->all();

        $view = $this->requestGuard === 'web'
            ? 'Request::livewire.requester.request-detail-client'
            : 'Request::livewire.requester.request-detail';

        return view($view, [
            'request' => $request,
            'schema' => $schema,
            'mineRouteName' => $this->requestRouteName('mine'),
        ]);
    }
}

<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Domain\Forms\VisibilityRuleEvaluator;

class RequestDetail extends Component
{
    public string $requestPublicId;

    public array $values = [];

    public int $lockVersion;

    public string $saveKey;

    public string $cancelKey;

    public bool $confirmingCancel = false;

    public function mount(string $requestPublicId, MyRequestsQuery $query): void
    {
        $this->requestPublicId = $requestPublicId;
        $request = $query->findVisible($requestPublicId, auth('admin')->user());
        Gate::authorize('view', $request);
        $this->values = (array) ($request->latestPayloadRevision?->payload_json ?? []);
        $this->lockVersion = $request->lock_version;
        $this->saveKey = (string) Str::uuid();
        $this->cancelKey = (string) Str::uuid();
    }

    public function save(MyRequestsQuery $query, SaveRequestDraft $service): void
    {
        $request = $query->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('update', $request);
        $service->handle($request, $this->values, (int) auth('admin')->id(), $this->lockVersion, $this->saveKey);
        $this->lockVersion = $request->refresh()->lock_version;
        $this->saveKey = (string) Str::uuid();
        session()->flash('request_success', __('Request::request.draft_saved'));
    }

    public function cancel(MyRequestsQuery $query, CancelInternalRequest $service): void
    {
        $request = $query->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('cancel', $request);
        $service->handle($request, (int) auth('admin')->id(), $this->lockVersion, $this->cancelKey);
        $this->redirectRoute('request.mine');
    }

    public function render(MyRequestsQuery $query, VisibilityRuleEvaluator $visibility)
    {
        $request = $query->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('view', $request);

        $schema = (array) $request->typeVersion->form_schema_json;
        $schema['sections'] = collect((array) ($schema['sections'] ?? []))->map(function (array $section) use ($visibility): array {
            $section['fields'] = collect((array) ($section['fields'] ?? []))->filter(fn (array $field): bool => $visibility->visible($field['visible_when'] ?? null, $this->values))->values()->all();

            return $section;
        })->all();

        return view('Request::livewire.requester.request-detail', ['request' => $request, 'schema' => $schema]);
    }
}

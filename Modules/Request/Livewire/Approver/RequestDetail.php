<?php

namespace Modules\Request\Livewire\Approver;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;
use Modules\Request\Models\RequestTask;

class RequestDetail extends Component
{
    use InteractsWithRequestAuthorization;

    public string $requestPublicId;

    public function mount(string $requestPublicId, RequestAuthorizationContext $context): void
    {
        $this->initializeRequestAuthorization($context);
        $this->requestPublicId = $requestPublicId;
    }

    public function render(MyRequestsQuery $query, RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $request = $query->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('view', $request);

        $task = $request->runs
            ->flatMap(fn ($run) => $run->tasks)
            ->filter(fn (RequestTask $task): bool => $task->assignee_user_id === (int) $user->getAuthIdentifier())
            ->sortByDesc('id')
            ->first();

        abort_if($task === null, 404);
        Gate::forUser($user)->authorize('view', $task);

        $values = $request->latestPayloadRevision
            ? (array) $request->latestPayloadRevision->payload_json
            : [];

        return view('Request::livewire.approver.request-detail-client', [
            'request' => $request,
            'task' => $task,
            'values' => $values,
            'schema' => (array) $request->typeVersion->form_schema_json,
            'inboxRouteName' => $this->requestGuard === 'web' ? 'client.request.inbox' : 'request.inbox',
        ]);
    }
}

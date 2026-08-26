<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Queries\RequestCollaborationQuery;
use Modules\Request\Application\Services\AddRequestComment;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;
use Modules\Request\Models\RequestComment;

class CommentComposer extends Component
{
    use InteractsWithRequestAuthorization;
    use WithPagination;

    public string $requestPublicId;

    public int $requestVersion;

    public string $body = '';

    public string $idempotencyKey;

    public function mount(string $requestPublicId, int $requestVersion, RequestAuthorizationContext $context): void
    {
        $this->initializeRequestAuthorization($context);
        $this->requestPublicId = $requestPublicId;
        $this->requestVersion = $requestVersion;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function add(RequestCollaborationQuery $collaboration, AddRequestComment $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $this->validate(['body' => ['required', 'string', 'max:5000']]);
        $request = $collaboration->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('create', [RequestComment::class, $request]);
        $service->handle($request, $this->body, (int) $user->getAuthIdentifier(), $this->requestVersion, $this->idempotencyKey);
        $this->requestVersion = $request->refresh()->lock_version;
        $this->body = '';
        $this->idempotencyKey = (string) Str::uuid();
        $this->resetPage('commentsPage');
        $this->dispatch('request-version-changed', version: $this->requestVersion);
        session()->flash('request_comment_success', __('Request::request.comment_added'));
    }

    #[On('request-version-changed')]
    public function updateRequestVersion(int $version): void
    {
        $this->requestVersion = $version;
    }

    public function render(RequestCollaborationQuery $collaboration, RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $request = $collaboration->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('view', $request);

        return view('Request::livewire.requester.comment-composer', ['request' => $request, 'comments' => $collaboration->comments($request)]);
    }
}

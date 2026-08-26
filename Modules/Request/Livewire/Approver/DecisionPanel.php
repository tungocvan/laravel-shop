<?php

namespace Modules\Request\Livewire\Approver;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;
use Modules\Request\Models\RequestTask;

class DecisionPanel extends Component
{
    use InteractsWithRequestAuthorization;

    public string $taskPublicId;

    public int $requestVersion;

    public int $taskVersion;

    public string $idempotencyKey;

    public bool $confirming = false;

    public string $decision = 'approve';

    public string $reason = '';

    public function mount(string $taskPublicId, int $requestVersion, int $taskVersion, RequestAuthorizationContext $context): void
    {
        $this->initializeRequestAuthorization($context);
        $this->taskPublicId = $taskPublicId;
        $this->requestVersion = $requestVersion;
        $this->taskVersion = $taskVersion;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function updatedDecision(): void
    {
        $this->resetValidation('reason');
    }

    public function approve(ApproverInboxQuery $query, DecideRequestTask $service, RequestAuthorizationContext $context): void
    {
        $this->decision = 'approve';
        $this->decide($query, $service, $context);
    }

    public function decide(ApproverInboxQuery $query, DecideRequestTask $service, RequestAuthorizationContext $context): void
    {
        $validated = $this->validate([
            'decision' => ['required', 'in:approve,reject,return'],
            'reason' => $this->decision === 'approve'
                ? ['nullable', 'string', 'max:2000']
                : ['required', 'string', 'max:2000'],
        ]);

        $user = $this->requestActor($context);
        $userId = (int) $user->getAuthIdentifier();
        $task = $query->findActionable($this->taskPublicId, $userId);
        Gate::forUser($user)->authorize('decide', $task);

        $service->handle(
            $task,
            DecisionType::from($validated['decision']),
            $validated['reason'] ?? '',
            $userId,
            $this->requestVersion,
            $this->taskVersion,
            $this->idempotencyKey,
        );

        session()->flash('request_success', match ($validated['decision']) {
            'reject' => __('Request::request.reject'),
            'return' => __('Request::request.return'),
            default => __('Request::request.decision_approved'),
        });

        $this->redirectRoute($this->requestGuard === 'web' ? 'client.request.inbox' : 'request.inbox');
    }

    public function render(RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $task = RequestTask::query()
            ->select(['id', 'public_id', 'assignee_user_id', 'suspended_at'])
            ->where('public_id', $this->taskPublicId)
            ->firstOrFail();
        Gate::forUser($user)->authorize('view', $task);

        return view('Request::livewire.approver.decision-panel', [
            'suspended' => $task->suspended_at !== null,
        ]);
    }
}

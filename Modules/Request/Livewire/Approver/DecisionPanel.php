<?php

namespace Modules\Request\Livewire\Approver;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Models\RequestTask;

class DecisionPanel extends Component
{
    public string $taskPublicId;

    public int $requestVersion;

    public int $taskVersion;

    public string $idempotencyKey;

    public bool $confirming = false;

    public string $decision = 'approve';

    public string $reason = '';

    public function mount(string $taskPublicId, int $requestVersion, int $taskVersion): void
    {
        $this->taskPublicId = $taskPublicId;
        $this->requestVersion = $requestVersion;
        $this->taskVersion = $taskVersion;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function updatedDecision(): void
    {
        $this->resetValidation('reason');
    }

    public function approve(ApproverInboxQuery $query, DecideRequestTask $service): void
    {
        $this->decision = 'approve';
        $this->decide($query, $service);
    }

    public function decide(ApproverInboxQuery $query, DecideRequestTask $service): void
    {
        $validated = $this->validate([
            'decision' => ['required', 'in:approve,reject,return'],
            'reason' => $this->decision === 'approve'
                ? ['nullable', 'string', 'max:2000']
                : ['required', 'string', 'max:2000'],
        ]);

        $task = $query->findActionable($this->taskPublicId, (int) auth('admin')->id());
        Gate::authorize('decide', $task);

        $service->handle(
            $task,
            DecisionType::from($validated['decision']),
            $validated['reason'] ?? '',
            (int) auth('admin')->id(),
            $this->requestVersion,
            $this->taskVersion,
            $this->idempotencyKey,
        );

        session()->flash('request_success', match ($validated['decision']) {
            'reject' => __('Request::request.reject'),
            'return' => __('Request::request.return'),
            default => __('Request::request.decision_approved'),
        });

        $this->redirectRoute('request.inbox');
    }

    public function render()
    {
        $task = RequestTask::query()
            ->select(['id', 'public_id', 'suspended_at'])
            ->where('public_id', $this->taskPublicId)
            ->first();

        return view('Request::livewire.approver.decision-panel', [
            'suspended' => $task?->suspended_at !== null,
        ]);
    }
}

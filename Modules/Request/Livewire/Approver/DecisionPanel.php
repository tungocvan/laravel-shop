<?php

namespace Modules\Request\Livewire\Approver;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Services\DecideRequestTask;

class DecisionPanel extends Component
{
    public string $taskPublicId;

    public int $requestVersion;

    public int $taskVersion;

    public string $idempotencyKey;

    public bool $confirming = false;

    public function mount(string $taskPublicId, int $requestVersion, int $taskVersion): void
    {
        $this->taskPublicId = $taskPublicId;
        $this->requestVersion = $requestVersion;
        $this->taskVersion = $taskVersion;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function approve(ApproverInboxQuery $query, DecideRequestTask $service): void
    {
        $task = $query->findActionable($this->taskPublicId, (int) auth('admin')->id());
        Gate::authorize('decide', $task);
        $service->approve($task, (int) auth('admin')->id(), $this->requestVersion, $this->taskVersion, $this->idempotencyKey);
        session()->flash('request_success', __('Request::request.decision_approved'));
        $this->redirectRoute('request.inbox');
    }

    public function render()
    {
        return view('Request::livewire.approver.decision-panel');
    }
}

<?php

namespace Modules\Request\Livewire\Shared;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Request\Application\Queries\RequestCollaborationQuery;
use Modules\Request\Models\RequestAuditEvent;

class AuditTimeline extends Component
{
    use WithPagination;

    public string $requestPublicId;

    public function mount(string $requestPublicId): void
    {
        $this->requestPublicId = $requestPublicId;
    }

    public function render(RequestCollaborationQuery $collaboration)
    {
        $request = $collaboration->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('viewAny', [RequestAuditEvent::class, $request]);

        return view('Request::livewire.shared.audit-timeline', ['events' => $collaboration->audit($request)]);
    }
}

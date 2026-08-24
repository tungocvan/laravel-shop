<?php

namespace Modules\Request\Policies;

use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestAuditEventPolicy
{
    use ChecksAdminPermission;

    public function viewAny(mixed $user, InternalRequest $request): bool
    {
        return $this->hasPermission($user, 'request.audit.view') && (new InternalRequestPolicy)->view($user, $request);
    }

    public function view(mixed $user, RequestAuditEvent $event): bool
    {
        $event->loadMissing('requestInstance');

        return $event->requestInstance !== null
            && $this->hasPermission($user, 'request.audit.view')
            && (new InternalRequestPolicy)->view($user, $event->requestInstance);
    }
}

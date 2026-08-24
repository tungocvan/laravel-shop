<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestAttachmentPolicy
{
    use ChecksAdminPermission;

    public function upload(mixed $user, InternalRequest $request): bool
    {
        return $this->hasPermission($user, 'request.attachment.upload')
            && ! $request->archived_at
            && ! in_array($request->status, [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled], true)
            && (new InternalRequestPolicy)->view($user, $request);
    }

    public function download(mixed $user, RequestAttachment $attachment): bool
    {
        $attachment->loadMissing('requestInstance');
        if (! $this->hasPermission($user, 'request.attachment.download') || $attachment->removed_at || $attachment->scan_status !== AttachmentScanStatus::Clean || ! (new InternalRequestPolicy)->view($user, $attachment->requestInstance)) {
            return false;
        }

        return $attachment->classification !== AttachmentClassification::Confidential
            || $attachment->requestInstance->requester_id === (int) $user->getAuthIdentifier()
            || $this->hasPermission($user, 'request.instance.view-all');
    }
}

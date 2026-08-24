<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Data\NotificationPlan;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\User\Data\UserMailMessage;

final class RequestNotificationMessageFactory
{
    public function mail(NotificationPlan $plan): UserMailMessage
    {
        return new UserMailMessage(
            subject: __('Request::request.notification_subject', ['number' => $plan->requestNumber]),
            greeting: __('Request::request.notification_greeting'),
            lines: [
                __('Request::request.notification_summary', ['title' => $plan->requestTitle, 'number' => $plan->requestNumber]),
                __('Request::request.notification_status', ['status' => $plan->status]),
            ],
            actionLabel: __('Request::request.notification_open'),
            actionUrl: url('/admin/requests/'.$plan->requestPublicId),
        );
    }

    public function database(NotificationPlan $plan, RequestOutboxMessage $outbox): array
    {
        return [
            'event_public_id' => $outbox->public_id,
            'template_key' => $plan->templateKey,
            'template_version' => 1,
            'request_public_id' => $plan->requestPublicId,
            'request_number' => $plan->requestNumber,
            'request_title' => $plan->requestTitle,
            'status' => $plan->status,
            'action_url' => url('/admin/requests/'.$plan->requestPublicId),
        ];
    }
}

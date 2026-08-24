<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Request\Domain\Enums\NotificationDeliveryStatus;
use Modules\Request\Models\RequestNotificationDelivery;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Notifications\RequestDatabaseNotification;
use Modules\Request\Support\RequestRuntimeState;
use Modules\User\Contracts\UserMailGateway;
use Modules\User\Contracts\UserNotifier;
use RuntimeException;
use Throwable;

final class RequestNotificationDeliverer
{
    public function __construct(private readonly RequestRuntimeState $runtime, private readonly RequestNotificationPlanner $planner, private readonly RequestNotificationMessageFactory $messages, private readonly UserMailGateway $mail, private readonly UserNotifier $notifier) {}

    public function deliver(string $deliveryPublicId): bool
    {
        if (! $this->runtime->enabled()) {
            return false;
        }

        $delivery = DB::transaction(function () use ($deliveryPublicId): ?RequestNotificationDelivery {
            $locked = RequestNotificationDelivery::query()->lockForUpdate()->where('public_id', $deliveryPublicId)->first();
            if (! $locked || $locked->status === NotificationDeliveryStatus::Delivered || $locked->status === NotificationDeliveryStatus::Failed) {
                return null;
            }
            if ($locked->status === NotificationDeliveryStatus::Processing && $locked->last_attempt_at?->gt(now('UTC')->subSeconds((int) config('request.notifications.delivery_lease_seconds', 300)))) {
                return null;
            }
            $locked->update(['status' => NotificationDeliveryStatus::Processing, 'attempt_count' => $locked->attempt_count + 1, 'last_attempt_at' => now('UTC'), 'last_error_code' => null]);

            return $locked->refresh();
        });

        if (! $delivery) {
            return false;
        }

        try {
            $outbox = RequestOutboxMessage::query()->where('public_id', $delivery->outbox_public_id)->firstOrFail();
            $plan = collect($this->planner->plans($outbox))->first(fn ($plan): bool => $plan->recipientId === $delivery->recipient_id && $plan->templateKey === $delivery->template_key);
            if (! $plan) {
                throw new RuntimeException('notification_plan_unavailable');
            }

            $sent = match ($delivery->channel) {
                'database' => $this->notifier->notify($delivery->recipient_id, new RequestDatabaseNotification($this->messages->database($plan, $outbox))),
                'email' => $this->mail->sendToActive($delivery->recipient_id, $this->messages->mail($plan)),
                default => throw new RuntimeException('notification_channel_unsupported'),
            };

            if (! $sent) {
                throw new RuntimeException('recipient_unavailable');
            }

            $delivery->update(['status' => NotificationDeliveryStatus::Delivered, 'delivered_at' => now('UTC'), 'last_error_code' => null]);

            return true;
        } catch (Throwable $exception) {
            $errorCode = in_array($exception->getMessage(), ['notification_plan_unavailable', 'recipient_unavailable', 'notification_channel_unsupported'], true) ? $exception->getMessage() : 'notification_delivery_failed';
            $terminal = $delivery->attempt_count >= (int) config('request.notifications.delivery_max_attempts', 5);
            $delivery->update(['status' => $terminal ? NotificationDeliveryStatus::Failed : NotificationDeliveryStatus::Pending, 'last_error_code' => $errorCode]);
            Log::warning('Request notification delivery failed.', ['delivery_public_id' => $deliveryPublicId, 'channel' => $delivery->channel, 'error_code' => $errorCode]);

            if (! $terminal) {
                throw new RuntimeException($errorCode, previous: $exception);
            }

            return false;
        }
    }
}

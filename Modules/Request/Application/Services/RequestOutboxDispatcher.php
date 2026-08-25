<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Request\Domain\Enums\NotificationDeliveryStatus;
use Modules\Request\Jobs\DeliverRequestNotification;
use Modules\Request\Models\RequestNotificationDelivery;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Support\RequestRuntimeState;
use Throwable;

final class RequestOutboxDispatcher
{
    public function __construct(private readonly RequestRuntimeState $runtime, private readonly RequestNotificationPlanner $planner) {}

    public function dispatchDue(?int $limit = null): int
    {
        if (! $this->runtime->enabled()) return 0;
        $limit = min(max($limit ?? (int) config('request.notifications.outbox_batch_size', 50), 1), 100);
        $publicIds = RequestOutboxMessage::query()->whereNull('dispatched_at')->whereNull('failed_at')->where('available_at', '<=', now('UTC'))->orderBy('available_at')->orderBy('id')->limit($limit)->pluck('public_id');
        $dispatched = 0;
        foreach ($publicIds as $publicId) if ($this->dispatchOne((string) $publicId)) $dispatched++;
        return $dispatched;
    }

    public function dispatchOne(string $publicId): bool
    {
        if (! $this->runtime->enabled()) return false;
        $claimed = false;
        try {
            $deliveryIds = DB::transaction(function () use ($publicId, &$claimed): ?array {
                $outbox = RequestOutboxMessage::query()->lockForUpdate()->where('public_id', $publicId)->first();
                if (! $outbox || $outbox->dispatched_at || $outbox->failed_at || $outbox->available_at->isFuture()) return null;
                $outbox->update(['attempt_count' => $outbox->attempt_count + 1, 'available_at' => now('UTC')->addSeconds((int) config('request.notifications.outbox_lease_seconds', 120)), 'last_error_code' => null, 'last_error_at' => null]);
                $claimed = true;
                $configuredChannels = array_values(array_intersect(['database', 'email'], (array) config('request.notifications.channels', ['database', 'email'])));
                $deliveries = [];
                foreach ($this->planner->plans($outbox) as $plan) {
                    $channels = array_values(array_intersect($configuredChannels, $plan->channels));
                    foreach ($channels as $channel) {
                        $delivery = RequestNotificationDelivery::query()->firstOrCreate([
                            'logical_key' => $outbox->public_id.':'.$plan->templateKey.':v1', 'channel' => $channel, 'recipient_id' => $plan->recipientId,
                        ], [
                            'outbox_public_id' => $outbox->public_id, 'template_key' => $plan->templateKey, 'template_version' => 1, 'status' => NotificationDeliveryStatus::Pending,
                        ]);
                        if ($delivery->status !== NotificationDeliveryStatus::Delivered) $deliveries[] = $delivery->public_id;
                    }
                }
                return array_values(array_unique($deliveries));
            }, 3);
            if ($deliveryIds === null) return false;
            foreach ($deliveryIds as $deliveryId) DeliverRequestNotification::dispatch($deliveryId)->onQueue((string) config('request.notifications.queue', 'request-notifications'));
            RequestOutboxMessage::query()->where('public_id', $publicId)->whereNull('failed_at')->update(['dispatched_at' => now('UTC')]);
            return true;
        } catch (Throwable $exception) {
            $this->recordFailure($publicId, $claimed, 'outbox_dispatch_failed');
            Log::warning('Request outbox dispatch failed.', ['outbox_public_id' => $publicId, 'error_code' => 'outbox_dispatch_failed']);
            return false;
        }
    }

    private function recordFailure(string $publicId, bool $claimed, string $errorCode): void
    {
        DB::transaction(function () use ($publicId, $claimed, $errorCode): void {
            $outbox = RequestOutboxMessage::query()->lockForUpdate()->where('public_id', $publicId)->first();
            if (! $outbox || $outbox->dispatched_at) return;
            $attempts = $outbox->attempt_count + ($claimed ? 0 : 1);
            $maxAttempts = (int) config('request.notifications.outbox_max_attempts', 5);
            $terminal = $attempts >= $maxAttempts;
            $outbox->update(['attempt_count' => $attempts, 'last_error_code' => $errorCode, 'last_error_at' => now('UTC'), 'available_at' => now('UTC')->addSeconds($this->backoff($attempts)), 'failed_at' => $terminal ? now('UTC') : null]);
        });
    }

    private function backoff(int $attempt): int
    {
        $backoff = (array) config('request.notifications.outbox_backoff_seconds', [60, 300, 900, 3600]);
        return (int) ($backoff[min(max($attempt - 1, 0), count($backoff) - 1)] ?? 3600);
    }
}

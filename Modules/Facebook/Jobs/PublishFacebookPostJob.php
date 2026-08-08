<?php

namespace Modules\Facebook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Facebook\Exceptions\FacebookApiException;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Services\FacebookPublishingService;

class PublishFacebookPostJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $postId) {}

    public function uniqueId(): string
    {
        return 'facebook-post-'.$this->postId;
    }

    public function uniqueFor(): int
    {
        return (int) config('facebook.duplicate_lock_seconds', 300);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(FacebookPublishingService $publisher): void
    {
        $post = FacebookPost::query()->with(['page', 'media'])->find($this->postId);

        if (! $post || $post->facebook_post_id || ! $post->page?->is_active) {
            return;
        }

        if ($post->scheduled_at && $post->scheduled_at->isFuture()) {
            $this->release($post->scheduled_at->diffInSeconds(now()));

            return;
        }

        $updated = FacebookPost::query()
            ->whereKey($post->id)
            ->whereIn('status', [FacebookPost::STATUS_SCHEDULED, FacebookPost::STATUS_QUEUED])
            ->whereNull('facebook_post_id')
            ->update([
                'status' => FacebookPost::STATUS_PROCESSING,
                'processing_at' => now(),
                'attempts' => $post->attempts + 1,
            ]);

        if ($updated !== 1) {
            return;
        }

        $post = FacebookPost::query()->with(['page', 'media'])->findOrFail($this->postId);

        try {
            $publisher->publish($post);
        } catch (FacebookApiException $exception) {
            Log::channel('facebook')->warning('Facebook post publish failed', [
                'post_id' => $post->id,
                'page_id' => $post->facebook_page_id,
                'facebook_page_id' => $post->page?->page_id,
                'error_code' => $exception->error->code,
                'error_subcode' => $exception->error->subcode,
                'error_type' => $exception->error->type,
                'trace_id' => $exception->error->traceId,
            ]);

            if ($exception->retryable()) {
                throw $exception;
            }
        }
    }
}

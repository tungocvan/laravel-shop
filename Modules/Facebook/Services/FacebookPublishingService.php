<?php

namespace Modules\Facebook\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Facebook\DTO\FacebookPublishResult;
use Modules\Facebook\Exceptions\FacebookApiException;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Models\FacebookPostMedia;

class FacebookPublishingService
{
    public function __construct(
        private readonly FacebookGraphClient $client,
        private readonly FacebookRedactor $redactor,
    ) {}

    public function publish(FacebookPost $post): FacebookPublishResult
    {
        if ($post->facebook_post_id) {
            return new FacebookPublishResult(true, $post->facebook_post_id, $post->facebook_permalink, $post->meta_response ?? []);
        }

        return match ($post->post_type) {
            FacebookPost::TYPE_PHOTO => $this->publishPhoto($post),
            FacebookPost::TYPE_LINK => $this->publishLink($post),
            default => $this->publishText($post),
        };
    }

    public function publishText(FacebookPost $post): FacebookPublishResult
    {
        return $this->send($post, $post->page->page_id.'/feed', [
            'message' => $post->message,
        ]);
    }

    public function publishLink(FacebookPost $post): FacebookPublishResult
    {
        return $this->send($post, $post->page->page_id.'/feed', [
            'message' => $post->message,
            'link' => $post->link_url,
        ]);
    }

    public function publishPhoto(FacebookPost $post): FacebookPublishResult
    {
        $media = $post->media()->where('media_type', 'photo')->first();
        abort_unless($media, 422, 'Bài đăng ảnh cần có ít nhất một ảnh.');

        $absolutePath = Storage::disk($media->disk)->path($media->path);
        $response = $this->client->postMultipart($post->page->page_id.'/photos', [
            'caption' => $post->message,
            'published' => true,
        ], 'source', $absolutePath, $post->page->page_access_token);

        $media->update([
            'facebook_media_id' => $response['id'] ?? null,
            'status' => FacebookPostMedia::STATUS_UPLOADED,
        ]);

        return $this->markPublished($post, $response);
    }

    public function markFailed(FacebookPost $post, FacebookApiException $exception): void
    {
        $post->update([
            'status' => FacebookPost::STATUS_FAILED,
            'failed_at' => now(),
            'last_error_code' => $exception->error->code,
            'last_error_subcode' => $exception->error->subcode,
            'last_error_type' => $exception->error->type,
            'last_error_message' => $exception->error->message,
            'last_error_trace_id' => $exception->error->traceId,
        ]);
    }

    private function send(FacebookPost $post, string $endpoint, array $payload): FacebookPublishResult
    {
        try {
            $response = $this->client->post($endpoint, $payload, $post->page->page_access_token);

            return $this->markPublished($post, $response);
        } catch (FacebookApiException $exception) {
            $this->markFailed($post, $exception);
            throw $exception;
        }
    }

    private function markPublished(FacebookPost $post, array $response): FacebookPublishResult
    {
        return DB::transaction(function () use ($post, $response): FacebookPublishResult {
            $facebookPostId = (string) ($response['post_id'] ?? $response['id'] ?? '');

            $post->update([
                'status' => FacebookPost::STATUS_PUBLISHED,
                'published_at' => now(),
                'facebook_post_id' => $facebookPostId ?: null,
                'facebook_permalink' => isset($response['permalink_url']) ? (string) $response['permalink_url'] : null,
                'meta_response' => $this->redactor->redact($response),
                'last_error_code' => null,
                'last_error_subcode' => null,
                'last_error_type' => null,
                'last_error_message' => null,
                'last_error_trace_id' => null,
            ]);

            return new FacebookPublishResult(true, $facebookPostId ?: null, $post->facebook_permalink, $post->meta_response ?? []);
        });
    }
}

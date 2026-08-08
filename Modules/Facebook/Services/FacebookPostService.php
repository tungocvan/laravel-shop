<?php

namespace Modules\Facebook\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Facebook\Jobs\PublishFacebookPostJob;
use Modules\Facebook\Models\FacebookPost;

class FacebookPostService
{
    public function __construct(private readonly FacebookMediaService $media) {}

    public function paginate(array $filters = [], int|string $perPage = 10)
    {
        $query = FacebookPost::query()
            ->with('page')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($sub) use ($search): void {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('facebook_post_id', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id');

        return $perPage === 'All' ? $query->get() : $query->paginate((int) $perPage);
    }

    public function find(int $id): FacebookPost
    {
        return FacebookPost::query()->with(['page', 'media'])->findOrFail($id);
    }

    public function createDraft(array $data, ?UploadedFile $image = null): FacebookPost
    {
        return DB::transaction(function () use ($data, $image): FacebookPost {
            $post = FacebookPost::query()->create($this->normalize($data, FacebookPost::STATUS_DRAFT));

            if ($image) {
                $this->media->attachPhoto($post, $image);
            }

            return $post->refresh();
        });
    }

    public function updateDraft(int $id, array $data, ?UploadedFile $image = null): FacebookPost
    {
        return DB::transaction(function () use ($id, $data, $image): FacebookPost {
            $post = $this->find($id);
            abort_if(in_array($post->status, [FacebookPost::STATUS_PROCESSING, FacebookPost::STATUS_PUBLISHED], true), 422, 'Không thể sửa bài đang xử lý hoặc đã đăng.');
            $post->update($this->normalize($data, $post->status));

            if ($image) {
                $this->media->attachPhoto($post, $image);
            }

            return $post->refresh();
        });
    }

    public function queueNow(int $id): FacebookPost
    {
        return DB::transaction(function () use ($id): FacebookPost {
            $post = FacebookPost::query()->lockForUpdate()->findOrFail($id);
            abort_if($post->facebook_post_id, 422, 'Bài đã được đăng trước đó.');
            abort_unless(in_array($post->status, [FacebookPost::STATUS_DRAFT, FacebookPost::STATUS_FAILED, FacebookPost::STATUS_SCHEDULED], true), 422, 'Bài không ở trạng thái có thể đưa vào queue.');

            $post->update([
                'status' => FacebookPost::STATUS_QUEUED,
                'queued_at' => now(),
                'scheduled_at' => null,
            ]);

            PublishFacebookPostJob::dispatch($post->id)->onQueue(config('facebook.queue', 'facebook'));

            return $post->refresh();
        });
    }

    public function schedule(int $id, string $scheduledAt): FacebookPost
    {
        $post = $this->find($id);
        abort_if($post->facebook_post_id, 422, 'Bài đã được đăng trước đó.');

        $post->update([
            'status' => FacebookPost::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        return $post->refresh();
    }

    public function cancel(int $id): FacebookPost
    {
        $post = $this->find($id);
        abort_unless(in_array($post->status, [FacebookPost::STATUS_DRAFT, FacebookPost::STATUS_SCHEDULED, FacebookPost::STATUS_QUEUED, FacebookPost::STATUS_FAILED], true), 422, 'Không thể hủy bài ở trạng thái hiện tại.');
        $post->update(['status' => FacebookPost::STATUS_CANCELLED]);

        return $post->refresh();
    }

    public function duplicate(int $id): FacebookPost
    {
        return DB::transaction(function () use ($id): FacebookPost {
            $source = $this->find($id);
            $copy = $source->replicate(['facebook_post_id', 'facebook_permalink', 'published_at', 'failed_at', 'meta_response']);
            $copy->status = FacebookPost::STATUS_DRAFT;
            $copy->idempotency_key = (string) Str::uuid();
            $copy->scheduled_at = null;
            $copy->queued_at = null;
            $copy->processing_at = null;
            $copy->attempts = 0;
            $copy->save();

            foreach ($source->media as $media) {
                $copy->media()->create($media->only(['media_type', 'disk', 'path', 'original_name', 'mime_type', 'size', 'sort_order']));
            }

            return $copy->refresh();
        });
    }

    public function deleteDraft(int $id): void
    {
        $post = $this->find($id);
        abort_unless(in_array($post->status, [FacebookPost::STATUS_DRAFT, FacebookPost::STATUS_CANCELLED], true), 422, 'Chỉ xóa được bản nháp hoặc bài đã hủy.');
        $post->delete();
    }

    private function normalize(array $data, string $status): array
    {
        return [
            'facebook_page_id' => (int) $data['facebook_page_id'],
            'created_by' => Auth::guard('admin')->id(),
            'title' => $this->nullable($data['title'] ?? null),
            'message' => $this->nullable($data['message'] ?? null),
            'post_type' => $data['post_type'] ?? FacebookPost::TYPE_TEXT,
            'link_url' => $this->nullable($data['link_url'] ?? null),
            'status' => $status,
            'idempotency_key' => $data['idempotency_key'] ?? (string) Str::uuid(),
        ];
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

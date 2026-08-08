<?php

namespace Modules\Facebook\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Facebook\DTO\FacebookPageVerificationResult;
use Modules\Facebook\Exceptions\FacebookApiException;
use Modules\Facebook\Models\FacebookConnection;
use Modules\Facebook\Models\FacebookPage;

class FacebookPageService
{
    public function __construct(
        private readonly FacebookGraphClient $client,
        private readonly FacebookTokenMasker $masker,
    ) {}

    public function paginate(array $filters = [], int|string $perPage = 10)
    {
        $query = FacebookPage::query()
            ->with('connection')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($sub) use ($search): void {
                $sub->where('page_name', 'like', "%{$search}%")
                    ->orWhere('page_id', 'like', "%{$search}%");
            }))
            ->when(($filters['is_active'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->latest('id');

        return $perPage === 'All' ? $query->get() : $query->paginate((int) $perPage);
    }

    public function activeOptions(): array
    {
        return FacebookPage::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('page_name')
            ->get(['id', 'page_name', 'page_id'])
            ->map(fn (FacebookPage $page) => [
                'id' => $page->id,
                'name' => $page->page_name.' ('.$page->page_id.')',
            ])
            ->all();
    }

    public function syncPages(FacebookConnection $connection): Collection
    {
        $response = $this->client->get('me/accounts', [
            'fields' => 'id,name,category,picture{url},access_token,tasks',
        ], $connection->user_access_token);

        return DB::transaction(function () use ($connection, $response): Collection {
            return collect($response['data'] ?? [])->map(function (array $item) use ($connection): FacebookPage {
                return FacebookPage::query()->updateOrCreate(
                    [
                        'facebook_connection_id' => $connection->id,
                        'page_id' => (string) $item['id'],
                    ],
                    [
                        'page_name' => (string) ($item['name'] ?? ''),
                        'page_category' => $item['category'] ?? null,
                        'page_picture_url' => data_get($item, 'picture.data.url'),
                        'page_access_token' => $item['access_token'] ?? null,
                        'granted_tasks' => $item['tasks'] ?? [],
                        'is_active' => true,
                        'last_synced_at' => now(),
                        'last_error_code' => null,
                        'last_error_message' => null,
                    ],
                );
            });
        });
    }

    public function verifyPage(FacebookPage $page): FacebookPageVerificationResult
    {
        try {
            $data = $this->client->get($page->page_id, ['fields' => 'id,name,access_token'], $page->page_access_token);
            $page->update([
                'last_verified_at' => now(),
                'last_error_code' => null,
                'last_error_message' => null,
                'is_active' => true,
            ]);

            return new FacebookPageVerificationResult(true, 'Token Fanpage hợp lệ.', $data);
        } catch (FacebookApiException $exception) {
            $this->deactivateInvalidPage($page, $exception->error->message, $exception->error->code);

            return new FacebookPageVerificationResult(false, $exception->error->message);
        }
    }

    public function verifyById(int $pageId): FacebookPageVerificationResult
    {
        return $this->verifyPage(FacebookPage::query()->findOrFail($pageId));
    }

    public function deactivateInvalidPage(FacebookPage $page, string $reason, ?string $code = null): void
    {
        $page->update([
            'is_active' => false,
            'last_verified_at' => now(),
            'last_error_code' => $code,
            'last_error_message' => $reason,
        ]);
    }

    public function setDefault(int $pageId): FacebookPage
    {
        return DB::transaction(function () use ($pageId): FacebookPage {
            FacebookPage::query()->update(['is_default' => false]);
            $page = FacebookPage::query()->findOrFail($pageId);
            $page->update(['is_default' => true, 'is_active' => true]);

            return $page;
        });
    }

    public function toggleActive(int $pageId, bool $active): FacebookPage
    {
        $page = FacebookPage::query()->findOrFail($pageId);
        $page->update(['is_active' => $active]);

        return $page;
    }

    public function maskedToken(FacebookPage $page): string
    {
        return $this->masker->mask($page->page_access_token);
    }
}

<?php

namespace Modules\Facebook\Services;

use Modules\Facebook\Models\FacebookConnection;
use Modules\Facebook\Models\FacebookPage;
use Modules\Facebook\Models\FacebookPost;

class FacebookDashboardService
{
    public function summary(): array
    {
        return [
            'app_configured' => filled(config('facebook.app_id')) && filled(config('facebook.app_secret')),
            'connection' => FacebookConnection::query()->latest('id')->first(),
            'active_pages' => FacebookPage::query()->where('is_active', true)->count(),
            'draft_posts' => FacebookPost::query()->where('status', FacebookPost::STATUS_DRAFT)->count(),
            'scheduled_posts' => FacebookPost::query()->whereIn('status', [FacebookPost::STATUS_SCHEDULED, FacebookPost::STATUS_QUEUED])->count(),
            'published_posts' => FacebookPost::query()->where('status', FacebookPost::STATUS_PUBLISHED)->count(),
            'failed_posts' => FacebookPost::query()->where('status', FacebookPost::STATUS_FAILED)->count(),
            'last_synced_at' => FacebookPage::query()->max('last_synced_at'),
        ];
    }
}

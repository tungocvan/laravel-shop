<?php

namespace Modules\Facebook\Console;

use Illuminate\Console\Command;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Services\FacebookPostService;

class FacebookPostCommand extends Command
{
    protected $signature = 'facebook:post {--page=} {--message=} {--dry-run} {--publish}';

    protected $description = 'Tạo và tùy chọn đăng thử một bài Facebook Fanpage';

    public function handle(FacebookPostService $posts): int
    {
        if (! $this->option('page') || ! $this->option('message')) {
            $this->error('Cần truyền --page và --message.');

            return self::FAILURE;
        }

        $post = $posts->createDraft([
            'facebook_page_id' => (int) $this->option('page'),
            'message' => (string) $this->option('message'),
            'post_type' => FacebookPost::TYPE_TEXT,
            'title' => 'CLI test '.now()->format('Y-m-d H:i:s'),
        ]);

        if (! $this->option('publish')) {
            $this->info("Dry-run: đã tạo bản nháp #{$post->id}, chưa đăng thật. Thêm --publish để đưa vào queue.");

            return self::SUCCESS;
        }

        $posts->queueNow($post->id);
        $this->info("Đã đưa bài #{$post->id} vào queue Facebook.");

        return self::SUCCESS;
    }
}

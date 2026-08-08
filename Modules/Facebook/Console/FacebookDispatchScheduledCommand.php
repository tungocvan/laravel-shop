<?php

namespace Modules\Facebook\Console;

use Illuminate\Console\Command;
use Modules\Facebook\Jobs\PublishFacebookPostJob;
use Modules\Facebook\Models\FacebookPost;

class FacebookDispatchScheduledCommand extends Command
{
    protected $signature = 'facebook:dispatch-scheduled';

    protected $description = 'Đưa các bài Facebook đến hạn vào queue';

    public function handle(): int
    {
        $count = 0;

        FacebookPost::query()
            ->due()
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($posts) use (&$count): void {
                foreach ($posts as $post) {
                    $updated = FacebookPost::query()
                        ->whereKey($post->id)
                        ->where('status', FacebookPost::STATUS_SCHEDULED)
                        ->update([
                            'status' => FacebookPost::STATUS_QUEUED,
                            'queued_at' => now(),
                        ]);

                    if ($updated === 1) {
                        PublishFacebookPostJob::dispatch($post->id)->onQueue(config('facebook.queue', 'facebook'));
                        $count++;
                    }
                }
            });

        $this->info("Đã đưa {$count} bài đến hạn vào queue.");

        return self::SUCCESS;
    }
}

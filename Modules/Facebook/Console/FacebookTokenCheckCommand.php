<?php

namespace Modules\Facebook\Console;

use Illuminate\Console\Command;
use Modules\Facebook\Models\FacebookPage;
use Modules\Facebook\Services\FacebookPageService;

class FacebookTokenCheckCommand extends Command
{
    protected $signature = 'facebook:token-check {--page=} {--all}';

    protected $description = 'Kiểm tra token Fanpage Facebook';

    public function handle(FacebookPageService $pages): int
    {
        $query = FacebookPage::query();

        if ($this->option('page')) {
            $query->whereKey((int) $this->option('page'));
        } elseif (! $this->option('all')) {
            $this->error('Truyền --page=ID hoặc --all.');

            return self::FAILURE;
        }

        $query->each(function (FacebookPage $page) use ($pages): void {
            $result = $pages->verifyPage($page);
            $this->line('#'.$page->id.' '.$page->page_name.': '.($result->valid ? 'OK' : $result->message));
        });

        return self::SUCCESS;
    }
}

<?php

namespace Modules\Facebook\Console;

use Illuminate\Console\Command;
use Modules\Facebook\Models\FacebookConnection;
use Modules\Facebook\Models\FacebookPage;
use Modules\Facebook\Services\FacebookPageService;

class FacebookPagesCommand extends Command
{
    protected $signature = 'facebook:pages {--connection=} {--sync} {--verify}';

    protected $description = 'Liệt kê, đồng bộ hoặc kiểm tra Fanpage Facebook đã kết nối';

    public function handle(FacebookPageService $pages): int
    {
        if ($this->option('sync')) {
            $connection = FacebookConnection::query()
                ->when($this->option('connection'), fn ($query, $id) => $query->whereKey($id))
                ->latest('id')
                ->first();

            if (! $connection) {
                $this->error('Chưa có kết nối Facebook.');

                return self::FAILURE;
            }

            $count = $pages->syncPages($connection)->count();
            $this->info("Đã đồng bộ {$count} Fanpage.");
        }

        if ($this->option('verify')) {
            FacebookPage::query()->where('is_active', true)->each(fn (FacebookPage $page) => $pages->verifyPage($page));
        }

        $rows = FacebookPage::query()->latest('id')->get()->map(fn (FacebookPage $page) => [
            'ID' => $page->id,
            'Page ID' => $page->page_id,
            'Name' => $page->page_name,
            'Active' => $page->is_active ? 'yes' : 'no',
            'Default' => $page->is_default ? 'yes' : 'no',
            'Last sync' => $page->last_synced_at?->format('Y-m-d H:i:s') ?? '-',
            'Token' => $pages->maskedToken($page),
        ])->all();

        $this->table(['ID', 'Page ID', 'Name', 'Active', 'Default', 'Last sync', 'Token'], $rows);

        return self::SUCCESS;
    }
}

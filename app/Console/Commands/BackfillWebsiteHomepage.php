<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Website\Services\HomepageBackfillService;

class BackfillWebsiteHomepage extends Command
{
    protected $signature = 'website:backfill-homepage {--apply : Ghi dữ liệu structured homepage}';

    protected $description = 'Dry-run hoặc backfill idempotent homepage từ wp_settings';

    public function handle(HomepageBackfillService $service): int
    {
        $report = $service->backfill((bool) $this->option('apply'));
        $this->table(['Chỉ số', 'Giá trị'], collect($report)->map(fn ($value, $key): array => [
            $key,
            is_array($value) ? (empty($value) ? '-' : implode(', ', $value)) : (is_bool($value) ? ($value ? 'yes' : 'no') : $value),
        ])->values()->all());

        $this->line($this->option('apply')
            ? 'Backfill hoàn tất; wp_settings không bị thay đổi.'
            : 'DRY-RUN: không có dữ liệu nào bị thay đổi.');

        return self::SUCCESS;
    }
}

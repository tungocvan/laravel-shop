<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Modules\System\Services\SettingsService;

class HomepageContentWriteService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly HomepageBackfillService $backfill,
    ) {}

    public function save(array $values, array $sectionOrder = []): array
    {
        $result = DB::transaction(function () use ($values, $sectionOrder): array {
            // Compatibility write is intentionally retained until the structured
            // homepage has passed its rollback window.
            $this->settings->updateMany($values, 'homepage');

            return $this->backfill->backfill(true, $sectionOrder);
        });

        HomepageContentService::clearCache();

        return $result;
    }
}

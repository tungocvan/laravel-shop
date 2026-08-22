<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Modules\System\Services\SettingsService;

class HomepageContentWriteService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly HomepageBackfillService $backfill,
        private readonly HomepageBuilderPersistenceService $builderPersistence,
    ) {}

    public function save(
        array $values,
        array $sectionOrder = [],
        array $layout = [],
        array $sectionTypes = []
    ): array {
        $result = DB::transaction(function () use ($values, $sectionOrder, $layout, $sectionTypes): array {
            // Compatibility write is intentionally retained until the structured
            // homepage has passed its rollback window.
            $this->settings->updateMany($values, 'homepage');

            $report = $this->backfill->backfill(true, $sectionOrder);
            $this->builderPersistence->sync($sectionOrder, $layout, $sectionTypes);

            return $report;
        });

        HomepageContentService::clearCache();

        return $result;
    }
}

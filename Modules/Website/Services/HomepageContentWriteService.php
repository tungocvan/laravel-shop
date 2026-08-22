<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Modules\System\Services\SettingsService;
use Modules\Website\Models\WebsitePage;

class HomepageContentWriteService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly HomepageBackfillService $backfill,
        private readonly HomepageBuilderPersistenceService $builderPersistence,
        private readonly HomepageStructuredContentService $structuredContent,
    ) {}

    public function save(
        array $values,
        array $sectionOrder = [],
        array $layout = [],
        array $sectionTypes = []
    ): array {
        $result = DB::transaction(function () use ($values, $sectionOrder, $layout, $sectionTypes): array {
            $report = [
                'apply' => true,
                'source' => 'structured',
            ];

            // One-time safety bridge for installations that still only have legacy home_* settings.
            if (! WebsitePage::query()->where('slug', 'home')->exists()) {
                $report = $this->backfill->backfill(true, $sectionOrder);
                $report['source'] = 'legacy_backfill';
            }

            // Structured Homepage is the canonical write target from Phase 11F onward.
            $this->builderPersistence->sync($sectionOrder, $layout, $sectionTypes);
            $this->structuredContent->sync($values);

            // Compatibility mirror is intentionally retained during the rollback window.
            // Frontend/Admin no longer depend on it when structured Homepage exists.
            $this->settings->updateMany($values, 'homepage');

            return $report;
        });

        HomepageContentService::clearCache();

        return $result;
    }
}

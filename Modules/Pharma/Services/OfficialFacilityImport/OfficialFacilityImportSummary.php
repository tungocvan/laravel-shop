<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Modules\Pharma\Models\OfficialFacilityImportBatch;

class OfficialFacilityImportSummary
{
    public function refresh(OfficialFacilityImportBatch $batch): OfficialFacilityImportBatch
    {
        $counts = $batch->rows()
            ->selectRaw('classification, import_status, is_selected, count(*) as aggregate')
            ->groupBy('classification', 'import_status', 'is_selected')
            ->get();

        $total = $batch->rows()->count();
        $invalid = $batch->rows()->where('classification', 'INVALID')->count();
        $conflicts = $batch->rows()->whereIn('classification', ['LIKELY_MATCH', 'CONFLICT'])->count();

        $batch->update([
            'total_count' => $total,
            'valid_count' => max(0, $total - $invalid),
            'invalid_count' => $invalid,
            'selected_count' => $batch->rows()->where('is_selected', true)->count(),
            'created_count' => $batch->rows()->where('import_status', 'CREATED')->count(),
            'linked_count' => $batch->rows()->where('import_status', 'LINKED')->count(),
            'conflict_count' => $conflicts,
            'skipped_count' => $batch->rows()->where('import_status', 'like', 'SKIPPED%')->count(),
            'failed_count' => $batch->rows()->where('import_status', 'FAILED')->count(),
            'summary' => [
                'classifications' => $batch->rows()->selectRaw('classification, count(*) as aggregate')->groupBy('classification')->pluck('aggregate', 'classification')->all(),
                'outcomes' => $batch->rows()->whereNotNull('import_status')->selectRaw('import_status, count(*) as aggregate')->groupBy('import_status')->pluck('aggregate', 'import_status')->all(),
            ],
        ]);

        return $batch->refresh();
    }
}

<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\OfficialFacilityImportRow;
use RuntimeException;

class OfficialFacilityConflictResolver
{
    public function resolve(OfficialFacilityImportRow $row, string $action, ?int $partnerId, ?int $userId, ?string $note = null): OfficialFacilityImportRow
    {
        if (! in_array($row->classification, ['LIKELY_MATCH', 'CONFLICT'], true)) {
            throw new RuntimeException('Dòng này không cần xử lý xung đột.');
        }

        if ($action === 'link') {
            $partner = Partner::query()->findOrFail($partnerId);
            $row->update([
                'resolved_partner_id' => $partner->id,
                'resolution_status' => 'LINKED',
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'resolution_note' => $note,
            ]);
        } elseif ($action === 'create') {
            $row->update([
                'resolved_partner_id' => null,
                'resolution_status' => 'CREATE_NEW',
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'resolution_note' => $note,
            ]);
        } elseif ($action === 'skip') {
            $row->update([
                'is_selected' => false,
                'resolution_status' => 'SKIPPED',
                'resolved_partner_id' => null,
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'resolution_note' => $note,
                'import_status' => 'SKIPPED_MANUAL',
            ]);
        } else {
            throw new RuntimeException('Hành động xử lý không hợp lệ.');
        }

        return $row->refresh();
    }
}

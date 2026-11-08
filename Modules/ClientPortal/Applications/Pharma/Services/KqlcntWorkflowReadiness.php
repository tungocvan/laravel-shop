<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Services\KqlcntAwardReadService;

/**
 * Read-only readiness check before connecting canonical KQLCNT groups to Pharma
 * allocation and commercial-policy workflows. Never matches by TBMT alone.
 */
final class KqlcntWorkflowReadiness
{
    public function __construct(private readonly KqlcntAwardReadService $awards) {}

    /**
     * @return array{status: string, canonical_count: int, linked_count: int, reason: string}
     */
    public function forAward(int $awardItemId): array
    {
        $medicines = $this->awards->medicines($awardItemId);
        $ids = $medicines->pluck('id')->map(fn ($id) => (int) $id)->all();
        $count = count($ids);

        if ($count === 0) {
            return ['status' => 'unavailable', 'canonical_count' => 0, 'linked_count' => 0, 'reason' => 'Hồ sơ không có sản phẩm canonical.'];
        }

        // source_id must point to the exact canonical line, not just the same TBMT.
        $links = DB::table('pharma_drug_bid_awards')
            ->where('source_type', 'muasamcong')
            ->whereIn('source_id', $ids)
            ->get(['id', 'source_id', 'bidding_notice_code', 'contractor_code']);

        $bySource = $links->groupBy(fn ($row) => (int) $row->source_id);
        $complete = $links->count() === $count && $bySource->count() === $count
            && $bySource->every(fn ($rows) => $rows->count() === 1);

        $identityMatches = $complete && $medicines->every(function ($medicine) use ($bySource): bool {
            $linked = $bySource->get((int) $medicine->id)?->first();

            return $linked !== null
                && trim((string) $linked->bidding_notice_code) === trim((string) $medicine->notify_no)
                && trim((string) $linked->contractor_code) === trim((string) $medicine->contractor_code);
        });

        return [
            'status' => $identityMatches ? 'linked' : 'requires_mapping',
            'canonical_count' => $count,
            'linked_count' => $bySource->count(),
            'reason' => $identityMatches
                ? 'Các dòng canonical đã có liên kết Pharma duy nhất.'
                : 'Chưa có liên kết 1–1 được xác thực giữa KQLCNT và Pharma; không cho phép thao tác ghi.',
        ];
    }
}

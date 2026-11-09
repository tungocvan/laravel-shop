<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Modules\Muasamcong\Services\KqlcntAwardReadService;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

/**
 * Read-only explanation of why canonical KQLCNT cannot enter the legacy
 * TBMT-wide allocation workflow. Never treats a text candidate as a mapping.
 */
final class KqlcntAllocationBlockers
{
    public function __construct(
        private readonly KqlcntAwardReadService $canonical,
        private readonly KqlcntLegacyWorkflowBridge $bridge,
        private readonly DrugBidAwardResultGroupService $groups,
    ) {}

    public function inspect(int $awardItemId): array
    {
        $lines = $this->canonical->medicines($awardItemId);
        if ($lines->isEmpty()) {
            return ['status' => 'no_products', 'reason' => 'Hồ sơ không có dòng thuốc canonical.', 'legacy_count' => 0];
        }

        if ($this->bridge->scopeKey($awardItemId) !== null) {
            return ['status' => 'ready', 'reason' => 'Đã xác minh phạm vi trùng khớp workflow Pharma.', 'legacy_count' => $lines->count()];
        }

        $notice = trim((string) $lines->first()->notify_no);
        if ($notice === '') {
            return ['status' => 'missing_notice', 'reason' => 'Hồ sơ thiếu mã TBMT.', 'legacy_count' => 0];
        }

        $legacy = DrugBidAward::query()->where('bidding_notice_code', $notice)
            ->get(['id', 'source_type', 'source_id', 'contractor_code', 'winning_company_name', 'medicine_name', 'quantity', 'winning_price']);
        if ($legacy->isEmpty()) {
            return ['status' => 'missing_pharma', 'reason' => 'Chưa có dòng Pharma cùng mã TBMT; không thể lưu phân bổ trên workflow cũ.', 'legacy_count' => 0];
        }

        $contractorCode = trim((string) $lines->first()->contractor_code);
        $contractorName = trim((string) $lines->first()->contractor_name);
        $sameContractor = $legacy->filter(fn ($row): bool =>
            $contractorCode !== ''
                ? trim((string) $row->contractor_code) === $contractorCode
                    && ($contractorName === '' || trim((string) $row->winning_company_name) === $contractorName)
                : $contractorName !== '' && trim((string) $row->winning_company_name) === $contractorName
        );

        if ($sameContractor->isEmpty()) {
            return ['status' => 'contractor_mismatch', 'reason' => 'Có Pharma cùng TBMT nhưng chưa xác minh được đúng nhà thầu.', 'legacy_count' => $legacy->count()];
        }

        if ($legacy->count() !== $sameContractor->count()) {
            return ['status' => 'shared_tbmt_scope', 'reason' => 'Workflow Pharma cũ gom nhiều nhà thầu theo TBMT; cần tách phạm vi nghiệp vụ trước khi cho phép ghi.', 'legacy_count' => $legacy->count()];
        }

        if ($sameContractor->count() !== $lines->count()) {
            return ['status' => 'product_count_mismatch', 'reason' => 'Số dòng Pharma và KQLCNT không khớp; cần đối chiếu định danh sản phẩm.', 'legacy_count' => $legacy->count()];
        }

        return ['status' => 'identity_unverified', 'reason' => 'Số dòng và nhà thầu có vẻ phù hợp nhưng chưa đủ bằng chứng liên kết sản phẩm 1–1; không được tự động ghi.', 'legacy_count' => $legacy->count()];
    }
}

<?php

namespace Modules\Order\Services;

use App\Models\User;
use Modules\Order\Models\AffiliateLevel;
use Modules\Order\Models\Order;

class AffiliateRankService
{
    /**
     * Kiểm tra và thăng hạng cho User dựa trên doanh số thành công.
     */
    public function checkAndUpdateRank(int $userId): void
    {
        $user = User::findOrFail($userId);

        $totalRevenue = Order::where('affiliate_id', $userId)
            ->where('commission_status', 'approved')
            ->sum('total');

        $eligibleLevel = AffiliateLevel::where('min_revenue_required', '<=', $totalRevenue)
            ->orderBy('min_revenue_required', 'desc')
            ->first();

        if ($eligibleLevel && $user->affiliate_level_id !== $eligibleLevel->id) {
            $user->update([
                'affiliate_level_id' => $eligibleLevel->id,
            ]);
        }
    }
}

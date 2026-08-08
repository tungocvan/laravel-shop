<?php
namespace Modules\Admin\Services;

use App\Models\User;
use Modules\Admin\Models\AffiliateLevel;
use Modules\Website\Models\Order;

class AffiliateRankService
{
    /**
     * Kiểm tra và thăng hạng cho User dựa trên doanh số thành công
     */
    public function checkAndUpdateRank(int $userId): void
    {
        $user = User::findOrFail($userId);

        // 1. Tính tổng doanh thu từ các đơn hàng đã được DUYỆT hoa hồng
        $totalRevenue = Order::where('affiliate_id', $userId)
            ->where('commission_status', 'approved')
            ->sum('total');

        // 2. Tìm cấp độ cao nhất mà User đạt đủ điều kiện doanh số
        // Lấy level có min_revenue_required <= doanh thu hiện tại, sắp xếp giảm dần
        $eligibleLevel = AffiliateLevel::where('min_revenue_required', '<=', $totalRevenue)
            ->orderBy('min_revenue_required', 'desc')
            ->first();

        // 3. Nếu tìm thấy level mới cao hơn level hiện tại thì cập nhật
        if ($eligibleLevel && $user->affiliate_level_id !== $eligibleLevel->id) {
            $user->update([
                'affiliate_level_id' => $eligibleLevel->id
            ]);

            // TODO: Bắn Notification/Email chúc mừng cho Đối tác
            // $user->notify(new \App\Notifications\AffiliateRankUp($eligibleLevel));
        }
    }
}
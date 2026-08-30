<?php

namespace Modules\Website\Services;

use Exception;
use Modules\Order\Models\Order;

class AdminAffiliateService
{
    /**
     * Lấy danh sách hoa hồng (Có phân trang & bộ lọc).
     */
    public function getCommissions(array $filters = [], int $perPage = 10)
    {
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $level = $filters['level'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));

        return Order::query()
            ->whereNotNull('affiliate_id')
            ->with(['affiliate', 'user', 'items'])
            ->when(isset($filters['status']) && $filters['status'] !== 'all', function ($query) use ($filters) {
                $query->where('commission_status', $filters['status']);
            })
            ->when($level !== 'all', function ($query) use ($level) {
                $query->whereHas('affiliate', function ($affiliateQuery) use ($level) {
                    $affiliateQuery->where('affiliate_level_id', $level);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where('order_code', 'like', '%'.$search.'%');
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Từ chối hoa hồng kèm lý do.
     */
    public function reject($orderId, $reason)
    {
        $order = Order::findOrFail($orderId);

        if ($order->commission_status !== 'pending') {
            throw new Exception('Trạng thái đơn hàng không hợp lệ để từ chối.');
        }

        $order->update([
            'commission_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $order;
    }

    public function getOrderDetail($orderId)
    {
        return Order::with(['items', 'user', 'affiliate'])
            ->findOrFail($orderId);
    }

    /**
     * Duyệt hoa hồng và cập nhật hạng đối tác.
     */
    public function approve($orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->commission_status === 'approved') {
            throw new Exception('Hoa hồng này đã được duyệt trước đó.');
        }

        return \DB::transaction(function () use ($order) {
            $order->update(['commission_status' => 'approved']);

            $rankService = app(AffiliateRankService::class);
            $rankService->checkAndUpdateRank($order->affiliate_id);

            return $order;
        });
    }
}

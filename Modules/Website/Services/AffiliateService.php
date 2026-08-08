<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Models\AffiliateScheme;
use Modules\Website\Models\Order;

class AffiliateService
{
    /**
     * Lấy ID người giới thiệu hợp lệ từ Cookie
     */
    public function getValidAffiliateId(): ?int
    {
        $affiliateId = Cookie::get('affiliate_ref');

        // Logic bảo vệ: Nếu User đang login trùng với ID trong cookie -> Hủy (Không tự ref)
        if (Auth::check() && Auth::id() == $affiliateId) {
            return null;
        }

        return $affiliateId ? (int)$affiliateId : null;
    }

    /**
     * Tính toán hoa hồng (Ví dụ: 10% giá trị đơn hàng)
     */
    public function calculateCommission(float $orderSubtotal): float
    {
        return $orderSubtotal * 0.10;
    }

    /**
     * Lấy thống kê tổng quan cho User
     */
    public function getStats($userId)
    {
        // Lấy tất cả đơn hàng do user này giới thiệu
        $query = Order::where('affiliate_id', $userId);

        return [
            // Tổng thu nhập (Đã duyệt)
            'total_earnings' => $query->clone()
                ->where('commission_status', 'approved')
                ->sum('commission_amount'),

            // Thu nhập chờ duyệt (Đơn mới đặt, chưa đối soát)
            'pending_earnings' => $query->clone()
                ->where('commission_status', 'pending')
                ->sum('commission_amount'),

            // Tổng số đơn hàng giới thiệu thành công
            'total_orders' => $query->count(),
        ];
    }

    /**
     * Lấy lịch sử hoa hồng (Có lọc trạng thái)
     */
    public function getCommissionHistory($userId, $status = 'all', $limit = 10)
    {
        return Order::where('affiliate_id', $userId)
            ->with(['items']) // Eager load sản phẩm để hiển thị trong modal
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('commission_status', $status);
            })
            ->select('id', 'order_code', 'customer_name', 'total', 'commission_amount', 'commission_status', 'rejection_reason', 'created_at')
            ->latest()
            ->paginate($limit);
    }

    /**
     * Lấy chi tiết 1 đơn hàng của Affiliate (Để xem modal)
     */
    public function getAffiliateOrderDetail($orderId, $affiliateId)
    {
        return Order::where('id', $orderId)
            ->where('affiliate_id', $affiliateId)
            ->with(['items']) // Load items để lấy commission_rate và commission_amount
            ->firstOrFail();
    }
    /**
     * Tính toán hoa hồng chi tiết cho từng sản phẩm trong giỏ hàng
     * @param array $cartItems Danh sách sản phẩm từ giỏ hàng
     * @return array Trả về mảng chứa thông tin hoa hồng để lưu vào OrderItem
     */
    public function calculateItemsCommission(array $cartItems): array
    {
        $defaultRate = 10; // Tỷ lệ mặc định 10% (Có thể lấy từ config/database settings)
        $processedItems = [];
        $totalOrderCommission = 0;

        foreach ($cartItems as $item) {
            // 1. Lấy thông tin sản phẩm từ DB để lấy % hoa hồng cấu hình riêng
            $product = \Modules\Website\Models\WpProduct::find($item['product_id']);

            // 2. Xác định tỷ lệ: Ưu tiên rate tại SP, nếu null thì dùng mặc định
            $rate = ($product && $product->affiliate_commission_rate !== null)
                ? (float)$product->affiliate_commission_rate
                : (float)$defaultRate;

            // 3. Tính số tiền hoa hồng cho dòng hàng này
            // Công thức: (Giá * Số lượng) * (Tỷ lệ / 100)
            $itemTotal = (float)$item['price'] * (int)$item['quantity'];
            $commissionAmount = ($itemTotal * $rate) / 100;

            $processedItems[] = [
                'product_id'        => $item['product_id'],
                'commission_rate'   => $rate,
                'commission_amount' => $commissionAmount,
            ];

            $totalOrderCommission += $commissionAmount;
        }

        return [
            'items' => $processedItems,
            'total_commission' => $totalOrderCommission
        ];
    }

    /**
     * Tính toán hoa hồng Hybrid cho một sản phẩm cụ thể dựa trên User giới thiệu
     */
    public function calculateHybridCommission(int $productId, int $affiliateId, float $price, int $qty): array
    {
        $affiliate = \App\Models\User::with('level')->find($affiliateId);
        $levelId = $affiliate?->affiliate_level_id;

        // 1. Tìm cấu hình phù hợp nhất theo trọng số ưu tiên
        $scheme = AffiliateScheme::where('product_id', $productId)
            ->where(function ($query) use ($affiliateId, $levelId) {
                $query->where('user_id', $affiliateId) // Ưu tiên 1: Cá nhân
                      ->orWhere('level_id', $levelId); // Ưu tiên 2: Cấp bậc
            })
            ->where('is_active', true)
            ->orderByRaw("user_id DESC") // Đảm bảo user_id (nếu có) luôn lên trước level_id
            ->first();

        // 2. Khởi tạo giá trị mặc định nếu không tìm thấy Scheme đặc biệt
        $type = $scheme ? $scheme->commission_type : 'percentage';
        $percent = 0;
        $fixed = 0;

        if ($scheme) {
            $percent = (float)$scheme->percent_value;
            $fixed = (float)$scheme->fixed_value;
        } else {
            // Mức 3: Lấy từ bảng wp_products
            $product = WpProduct::find($productId);
            $percent = $product->affiliate_commission_rate ?? 10; // Mức 4: Mặc định 10%
        }

        // 3. Thực thi công thức Hybrid
        // Formula: (Price * Qty * %/100) + (Fixed * Qty)
        $commissionFromPercent = ($price * $qty) * ($percent / 100);
        $commissionFromFixed = $fixed * $qty;
        $totalCommission = $commissionFromPercent + $commissionFromFixed;

        return [
            'type' => $type,
            'rate' => $percent,
            'fixed_unit_amount' => $fixed,
            'total_amount' => $totalCommission,
            'level_name' => $affiliate?->level?->name ?? 'Vãng lai'
        ];
    }
}

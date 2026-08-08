<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Modules\Website\Models\Order;
use Modules\Website\Models\OrderItem;
use Modules\Website\Models\OrderHistory;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Coupon;
use Exception;

class CheckoutService
{
    protected $cartService;
    protected $affiliateService;

    // Inject cả CartService và AffiliateService
    public function __construct(
        CartService $cartService,
        AffiliateService $affiliateService
    ) {
        $this->cartService = $cartService;
        $this->affiliateService = $affiliateService;
    }

    /**
     * Xử lý tạo đơn hàng (Core Logic)
     */
    public function createOrder(array $data)
    {
        // 1. Lấy dữ liệu từ CartService (Đã bao gồm tính toán Coupon, Subtotal, Total)

        $cartSummary = $this->cartService->getCartSummary();
        $cart = $cartSummary['cart'];
        $items = $cartSummary['items'];

        // Validate giỏ hàng trống
        if ($items->isEmpty()) {
            throw new Exception('Giỏ hàng trống. Vui lòng chọn sản phẩm.');
        }

        // 2. Validate Tồn kho (Double Check trước khi Transaction)
        foreach ($items as $item) {
            $product = WpProduct::find($item->product_id);

            if (!$product || !$product->is_active) {
                throw new Exception("Sản phẩm '{$item->product->title}' hiện ngừng kinh doanh.");
            }

            if ($product->quantity < $item->quantity) {
                throw new Exception("Sản phẩm '{$item->product->title}' không đủ hàng (Còn lại: {$product->quantity}).");
            }
        }

        return DB::transaction(function () use ($data, $cartSummary, $cart, $items) {

            // --- A. XỬ LÝ AFFILIATE ---
            $affiliateId = $this->affiliateService->getValidAffiliateId();
            $commissionAmount = 0;
            $commissionStatus = 'none';

            // Chỉ tính hoa hồng nếu có affiliate và người mua != người giới thiệu
            if ($affiliateId && $affiliateId != Auth::id()) {
                // Tính hoa hồng dựa trên Subtotal (Tổng tiền hàng chưa trừ KM)
                $commissionAmount = $this->affiliateService->calculateCommission($cartSummary['subtotal']);
                $commissionStatus = 'pending';
            } else {
                $affiliateId = null; // Reset nếu trùng ID
            }

            // --- B. XÁC ĐỊNH TRẠNG THÁI BAN ĐẦU ---
            $initialStatus = match ($data['payment_method']) {
                'momo', 'vnpay', 'bank_transfer' => 'pending_payment', // Chờ thanh toán
                default => 'pending', // Chờ xử lý (COD)
            };
            $orderNew= [
                'user_id'           => Auth::id(),
                'order_code'        => $this->generateOrderCode(),

                // Affiliate Info
                'affiliate_id'      => $affiliateId,
                'commission_status' => $commissionStatus,
                'commission_amount' => $commissionAmount,

                // Customer Info
                'customer_name'     => $data['customer_name'],
                'customer_phone'    => $data['customer_phone'],
                'customer_email'    => $data['customer_email'],
                'customer_address'  => $data['customer_address'],
                'note'              => $data['note'] ?? null,

                // Financial Info (Lấy từ CartSummary)
                'subtotal'          => $cartSummary['subtotal'],
                'shipping_fee'      => 0, // Sau này update nếu có tính phí ship
                'discount'          => $cartSummary['discount'], // Số tiền giảm giá
                'total'             => $cartSummary['total'],    // Tổng phải trả

                // Coupon Info (Snapshot để đối soát)
                'coupon_code'       => $cartSummary['coupon_code'] ?? null,

                // Meta
                'payment_method'    => $data['payment_method'],
                'status'            => $initialStatus,
            ];
            // --- C. TẠO ORDER ---

            $order = Order::create($orderNew);

            // --- D. TẠO ORDER ITEMS & TRỪ KHO ---
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->title, // Snapshot tên
                    'price'        => $item->price,          // Snapshot giá
                    'quantity'     => $item->quantity,
                    'total'        => $item->total,
                    'options'      => null, // Mở rộng sau này (Size/Color)
                ]);

                // Trừ kho & Tăng lượt bán
                $item->product->decrement('quantity', $item->quantity);
                $item->product->increment('sold_count', $item->quantity);
            }

            // --- E. XỬ LÝ COUPON (Nếu có dùng) ---
            if ($cart->coupon_id) {
                // Tăng số lần sử dụng của mã giảm giá
                $cart->coupon->increment('usage_count');
            }

            // --- F. GHI LOG LỊCH SỬ ---
            OrderHistory::create([
                'order_id'    => $order->id,
                'user_id'     => Auth::id(), // User hoặc Null (Guest)
                'action'      => 'created',
                'description' => 'Đơn hàng được tạo mới qua Website.',
            ]);

            // --- G. DỌN DẸP ---
            // Xóa items trong giỏ
            $cart->items()->delete();
            // Gỡ coupon khỏi cart và xóa cart
            $cart->delete();
            // 3. Xóa Session Coupon (Quan trọng)
            $cart->coupon_id = null;
            $cart->save(); // Save lần cuối để chắc chắn
            return $order;
        });
    }

    /**
     * Helper: Tạo mã đơn hàng chuyên nghiệp (VD: ORD-20231025-X8K2)
     */
    protected function generateOrderCode()
    {
        do {
            $date = now()->format('Ymd');
            $random = strtoupper(Str::random(4));
            $code = "ORD-{$date}-{$random}";
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}

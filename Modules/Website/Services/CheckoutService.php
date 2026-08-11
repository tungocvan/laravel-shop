<?php

namespace Modules\Website\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderHistory;
use Modules\Order\Models\OrderItem;
use Modules\Product\Models\Product;
use Modules\Website\Models\Cart;
use Modules\Website\Models\Coupon;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Tạo đơn hàng an toàn trong một transaction duy nhất.
     */
    public function createOrder(array $data): Order
    {
        $paymentMethod = (string) ($data['payment_method'] ?? '');

        if (! in_array($paymentMethod, ['cod', 'bank_transfer', 'momo'], true)) {
            throw new Exception('Phương thức thanh toán không được hỗ trợ.');
        }

        // Lấy đúng cart hiện tại trước khi vào transaction. Cart row sẽ được lock
        // bên trong transaction để ngăn hai request checkout cùng một giỏ hàng.
        $cart = $this->cartService->getCart();
        $cartId = $cart->id;

        return DB::transaction(function () use ($data, $paymentMethod, $cartId) {
            $lockedCart = Cart::query()
                ->whereKey($cartId)
                ->lockForUpdate()
                ->first();

            if (! $lockedCart) {
                throw new Exception('Giỏ hàng đã được xử lý. Vui lòng kiểm tra lại đơn hàng.');
            }

            $items = $lockedCart->items()->get();

            if ($items->isEmpty()) {
                throw new Exception('Giỏ hàng trống. Vui lòng chọn sản phẩm.');
            }

            // Lock toàn bộ sản phẩm theo thứ tự ID để giảm khả năng deadlock và
            // thực hiện final stock validation ngay trong transaction.
            $productIds = $items->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw new Exception("Sản phẩm '{$item->product_id}' hiện ngừng kinh doanh.");
                }

                if ((int) $product->quantity < (int) $item->quantity) {
                    throw new Exception("Sản phẩm '{$product->title}' không đủ hàng (Còn lại: {$product->quantity}).");
                }
            }

            $subtotal = (float) $items->sum('total');
            $discount = 0.0;
            $coupon = null;

            if ($lockedCart->coupon_id) {
                $coupon = Coupon::query()
                    ->whereKey($lockedCart->coupon_id)
                    ->lockForUpdate()
                    ->first();

                if ($coupon && $coupon->is_valid && $subtotal >= (float) $coupon->min_order_value) {
                    $discount = $coupon->type === 'percent'
                        ? $subtotal * ((float) $coupon->value / 100)
                        : (float) $coupon->value;
                } else {
                    $coupon = null;
                    $lockedCart->coupon_id = null;
                    $lockedCart->save();
                }
            }

            $total = max(0, $subtotal - $discount);

            // Affiliate được tính trên subtotal đã đóng băng của transaction này.
            $affiliateId = $this->affiliateService->getValidAffiliateId();
            $commissionAmount = 0;
            $commissionStatus = 'none';

            if ($affiliateId && $affiliateId != Auth::id()) {
                $commissionAmount = $this->affiliateService->calculateCommission($subtotal);
                $commissionStatus = 'pending';
            } else {
                $affiliateId = null;
            }

            $initialStatus = $paymentMethod === 'cod' ? 'pending' : 'pending_payment';

            $order = Order::create([
                'user_id' => Auth::id(),
                'order_code' => $this->generateOrderCode(),
                'affiliate_id' => $affiliateId,
                'commission_status' => $commissionStatus,
                'commission_amount' => $commissionAmount,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_address' => $data['customer_address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount' => $discount,
                'total' => $total,
                'coupon_code' => $coupon?->code,
                'payment_method' => $paymentMethod,
                'status' => $initialStatus,
            ]);

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->title,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'options' => null,
                ]);

                $product->decrement('quantity', $item->quantity);
                $product->increment('sold_count', $item->quantity);
            }

            if ($coupon) {
                $coupon->increment('usage_count');
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'created',
                'description' => 'Đơn hàng được tạo mới qua Website.',
            ]);

            // Xóa items rồi xóa cart đúng một lần. Không save model sau delete.
            $lockedCart->items()->delete();
            $lockedCart->delete();

            return $order;
        }, 3);
    }

    /**
     * Xác minh và xử lý kết quả MoMo. Callback không được tạo lại order,
     * trừ kho hay tăng coupon lần nữa.
     */
    public function processMomoResult(array $payload, MomoService $momoService): Order
    {
        if (! $momoService->verifyResultSignature($payload)) {
            throw new Exception('Chữ ký phản hồi MoMo không hợp lệ.');
        }

        $orderCode = (string) ($payload['orderId'] ?? '');

        return DB::transaction(function () use ($payload, $orderCode) {
            $order = Order::query()
                ->where('order_code', $orderCode)
                ->lockForUpdate()
                ->first();

            if (! $order || $order->payment_method !== 'momo') {
                throw new Exception('Không tìm thấy đơn hàng MoMo hợp lệ.');
            }

            $expectedAmount = (int) round((float) $order->total);
            $callbackAmount = (int) ($payload['amount'] ?? -1);

            if ($expectedAmount !== $callbackAmount) {
                throw new Exception('Số tiền phản hồi MoMo không khớp đơn hàng.');
            }

            // resultCode != 0: không đánh dấu đã thanh toán và không chạy lại
            // bất kỳ side effect checkout nào.
            if ((int) ($payload['resultCode'] ?? -1) !== 0) {
                if ($order->status === 'pending_payment') {
                    OrderHistory::create([
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'action' => 'momo_payment_failed',
                        'description' => 'Thanh toán MoMo chưa thành công: '.(string) ($payload['message'] ?? 'Unknown error'),
                    ]);
                }

                return $order;
            }

            // Idempotency: callback thành công lặp lại không tạo history/side effects mới.
            if ($order->status === 'pending_payment') {
                $order->update(['status' => 'pending']);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'action' => 'momo_paid',
                    'description' => 'MoMo xác nhận thanh toán thành công. Mã giao dịch: '.(string) ($payload['transId'] ?? ''),
                ]);
            }

            return $order->refresh();
        }, 3);
    }

    protected function generateOrderCode(): string
    {
        do {
            $date = now()->format('Ymd');
            $random = strtoupper(Str::random(4));
            $code = "ORD-{$date}-{$random}";
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}

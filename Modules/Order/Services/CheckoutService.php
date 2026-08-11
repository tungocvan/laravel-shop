<?php

namespace Modules\Order\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Order\Contracts\CheckoutContext;
use Modules\Order\Contracts\PaymentResultVerifier;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderHistory;
use Modules\Order\Models\OrderItem;
use Modules\Product\Models\Product;

class CheckoutService
{
    public function __construct(protected CheckoutContext $context) {}

    public function createOrder(array $data): Order
    {
        $paymentMethod = (string) ($data['payment_method'] ?? '');

        if (! in_array($paymentMethod, ['cod', 'bank_transfer', 'momo'], true)) {
            throw new Exception('Phương thức thanh toán không được hỗ trợ.');
        }

        $cartId = $this->context->currentCartId();
        $userId = $this->context->currentUserId();

        return DB::transaction(function () use ($data, $paymentMethod, $cartId, $userId): Order {
            $cart = $this->context->lockCart($cartId);
            $productIds = collect($cart->items)->pluck('productId')->unique()->sort()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $product = $products->get($item->productId);

                if (! $product || ! $product->is_active) {
                    throw new Exception("Sản phẩm '{$item->productId}' hiện ngừng kinh doanh.");
                }

                if ((int) $product->quantity < $item->quantity) {
                    throw new Exception("Sản phẩm '{$product->title}' không đủ hàng (Còn lại: {$product->quantity}).");
                }
            }

            $subtotal = (float) collect($cart->items)->sum(fn ($item): float => $item->total);
            $couponApplied = $cart->couponId !== null && $subtotal >= $cart->couponMinOrderValue;
            $discount = 0.0;

            if ($couponApplied) {
                $discount = $cart->couponType === 'percent'
                    ? $subtotal * ($cart->couponValue / 100)
                    : $cart->couponValue;
            }

            $attribution = $this->context->affiliateAttribution($subtotal, $userId);
            $orderAttributes = [
                'user_id' => $userId,
                'order_code' => $this->generateOrderCode(),
                'affiliate_id' => $attribution->affiliateId,
                'commission_status' => $attribution->commissionStatus,
                'commission_amount' => $attribution->commissionAmount,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_address' => $data['customer_address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount' => $discount,
                'total' => max(0, $subtotal - $discount),
                'payment_method' => $paymentMethod,
                'status' => $paymentMethod === 'cod' ? 'pending' : 'pending_payment',
            ];

            // Một số database production hiện tại chưa có cột coupon_code.
            // Phase 2 không thay đổi schema nên chỉ ghi khi cột đã tồn tại.
            if (Schema::hasColumn((new Order)->getTable(), 'coupon_code')) {
                $orderAttributes['coupon_code'] = $couponApplied ? $cart->couponCode : null;
            }

            $order = Order::create($orderAttributes);

            foreach ($cart->items as $item) {
                $product = $products->get($item->productId);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->productId,
                    'product_name' => $product->title,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'options' => null,
                ]);
                $product->decrement('quantity', $item->quantity);
                $product->increment('sold_count', $item->quantity);
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'action' => 'created',
                'description' => 'Đơn hàng được tạo mới qua Website.',
            ]);

            $this->context->consume($cart, $couponApplied);

            return $order;
        }, 3);
    }

    public function processMomoResult(array $payload, PaymentResultVerifier $verifier): Order
    {
        if (! $verifier->verifyResultSignature($payload)) {
            throw new Exception('Chữ ký phản hồi MoMo không hợp lệ.');
        }

        $orderCode = (string) ($payload['orderId'] ?? '');

        return DB::transaction(function () use ($payload, $orderCode): Order {
            $order = Order::query()->where('order_code', $orderCode)->lockForUpdate()->first();

            if (! $order || $order->payment_method !== 'momo') {
                throw new Exception('Không tìm thấy đơn hàng MoMo hợp lệ.');
            }

            if ((int) round((float) $order->total) !== (int) ($payload['amount'] ?? -1)) {
                throw new Exception('Số tiền phản hồi MoMo không khớp đơn hàng.');
            }

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
            $code = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }
}

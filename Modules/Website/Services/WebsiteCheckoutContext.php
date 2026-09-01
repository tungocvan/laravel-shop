<?php

namespace Modules\Website\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Modules\Order\Contracts\CheckoutContext;
use Modules\Order\Data\AffiliateAttribution;
use Modules\Order\Data\CheckoutCart;
use Modules\Order\Data\CheckoutCartItem;
use Modules\Order\Services\AffiliateService;
use Modules\Website\Models\Cart;
use Modules\Website\Models\Coupon;

class WebsiteCheckoutContext implements CheckoutContext
{
    public function __construct(
        protected CartService $cartService,
        protected AffiliateService $affiliateService,
    ) {}

    public function currentCartId(): int
    {
        return (int) $this->cartService->getCart()->id;
    }

    public function currentUserId(): ?int
    {
        return Auth::id();
    }

    public function lockCart(int $cartId): CheckoutCart
    {
        $cart = Cart::query()->whereKey($cartId)->lockForUpdate()->first();

        if (! $cart) {
            throw new Exception('Giỏ hàng đã được xử lý. Vui lòng kiểm tra lại đơn hàng.');
        }

        $items = $cart->items()->get();

        if ($items->isEmpty()) {
            throw new Exception('Giỏ hàng trống. Vui lòng chọn sản phẩm.');
        }

        $coupon = null;
        if ($cart->coupon_id) {
            $coupon = Coupon::query()->whereKey($cart->coupon_id)->lockForUpdate()->first();

            if (! $coupon || ! $coupon->is_valid) {
                $coupon = null;
                $cart->update(['coupon_id' => null]);
            }
        }

        return new CheckoutCart(
            id: (int) $cart->id,
            items: $items->map(fn ($item): CheckoutCartItem => new CheckoutCartItem(
                productId: (int) $item->product_id,
                price: (float) $item->price,
                quantity: (int) $item->quantity,
                total: (float) $item->total,
            ))->all(),
            couponId: $coupon?->id,
            couponCode: $coupon?->code,
            couponType: $coupon?->type,
            couponValue: (float) ($coupon?->value ?? 0),
            couponMinOrderValue: (float) ($coupon?->min_order_value ?? 0),
        );
    }

    public function affiliateAttribution(float $subtotal, ?int $userId): AffiliateAttribution
    {
        $affiliateId = $this->validAffiliateId();

        if ($affiliateId && $affiliateId !== $userId) {
            return new AffiliateAttribution(
                affiliateId: $affiliateId,
                commissionAmount: $this->affiliateService->calculateCommission($subtotal),
                commissionStatus: 'pending',
            );
        }

        return new AffiliateAttribution(null, 0, 'none');
    }

    public function consume(CheckoutCart $cart, bool $couponApplied): void
    {
        if ($couponApplied && $cart->couponId) {
            Coupon::query()->whereKey($cart->couponId)->increment('usage_count');
        }

        $lockedCart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
        $lockedCart->items()->delete();
        $lockedCart->delete();
    }

    private function validAffiliateId(): ?int
    {
        $affiliateId = Cookie::get('affiliate_ref');

        if (! $affiliateId) {
            return null;
        }

        $affiliateId = (int) $affiliateId;

        return Auth::check() && Auth::id() === $affiliateId ? null : $affiliateId;
    }
}

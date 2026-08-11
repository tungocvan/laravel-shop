<?php

namespace Modules\Order\Contracts;

use Modules\Order\Data\AffiliateAttribution;
use Modules\Order\Data\CheckoutCart;

interface CheckoutContext
{
    public function currentCartId(): int;

    public function currentUserId(): ?int;

    public function lockCart(int $cartId): CheckoutCart;

    public function affiliateAttribution(float $subtotal, ?int $userId): AffiliateAttribution;

    public function consume(CheckoutCart $cart, bool $couponApplied): void;
}

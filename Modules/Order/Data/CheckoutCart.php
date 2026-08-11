<?php

namespace Modules\Order\Data;

readonly class CheckoutCart
{
    /** @param  array<int, CheckoutCartItem>  $items */
    public function __construct(
        public int $id,
        public array $items,
        public ?int $couponId = null,
        public ?string $couponCode = null,
        public ?string $couponType = null,
        public float $couponValue = 0,
        public float $couponMinOrderValue = 0,
    ) {}
}

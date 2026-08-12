<?php

namespace Modules\Order\Data;

readonly class CheckoutCartItem
{
    public function __construct(
        public int $productId,
        public float $price,
        public int $quantity,
        public float $total,
    ) {}
}

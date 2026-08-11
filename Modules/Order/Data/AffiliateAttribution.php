<?php

namespace Modules\Order\Data;

readonly class AffiliateAttribution
{
    public function __construct(
        public ?int $affiliateId,
        public float $commissionAmount,
        public string $commissionStatus,
    ) {}
}

<?php

namespace Modules\Pharma\DTOs;

use Carbon\CarbonImmutable;

final readonly class ResolvedPrice
{
    public function __construct(
        public int $priceListId,
        public int $priceListItemId,
        public string $sourceType,
        public ?int $partnerId,
        public int $medicineId,
        public int $medicineVariantId,
        public ?int $medicinePackageId,
        public ?string $declaredPrice,
        public ?string $companySalePrice,
        public ?string $actualReceivablePrice,
        public ?string $invoicePrice,
        public string $currency,
        public ?string $effectiveFrom,
        public ?string $effectiveTo,
        public CarbonImmutable $resolvedAt,
    ) {}
};

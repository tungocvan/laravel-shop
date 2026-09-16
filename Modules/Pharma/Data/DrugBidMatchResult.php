<?php

namespace Modules\Pharma\Data;

use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;

final readonly class DrugBidMatchResult
{
    public function __construct(
        public ?Medicine $medicine,
        public ?MedicineVariant $variant,
        public ?MedicinePackage $package,
        public string $status,
        public ?string $method = null,
        public int $confidence = 0,
        public ?string $resolutionLevel = null,
        public ?string $reviewReason = null,
    ) {}
}

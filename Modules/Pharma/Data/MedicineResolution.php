<?php

namespace Modules\Pharma\Data;

use Modules\Pharma\Models\Medicine;

final readonly class MedicineResolution
{
    public function __construct(
        public ?Medicine $medicine,
        public string $status,
        public ?string $matchMethod = null,
        public int $confidence = 0,
    ) {}
}

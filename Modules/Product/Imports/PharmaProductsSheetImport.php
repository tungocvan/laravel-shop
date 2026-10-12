<?php

namespace Modules\Product\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class PharmaProductsSheetImport implements ToArray
{
    public function array(array $array): void
    {
        // Read-only adapter for Excel::toArray; no writes during preview.
    }
}

<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

class OfficialFacilityParser
{
    public const MAX_ROWS = 10000;

    public function parse(string $absolutePath): array
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new RuntimeException('Chỉ hỗ trợ tệp XLSX hoặc CSV.');
        }

        $rows = (new FastExcel)->import($absolutePath)->values();

        if ($rows->isEmpty()) {
            throw new RuntimeException('Tệp không có dữ liệu để import.');
        }

        if ($rows->count() > self::MAX_ROWS) {
            throw new RuntimeException('Tệp vượt quá giới hạn '.self::MAX_ROWS.' dòng.');
        }

        return $rows->map(fn ($row) => is_array($row) ? $row : $row->toArray())->all();
    }
}

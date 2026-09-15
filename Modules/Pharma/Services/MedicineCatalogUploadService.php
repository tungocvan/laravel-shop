<?php

namespace Modules\Pharma\Services;

use Illuminate\Http\UploadedFile;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

class MedicineCatalogUploadService
{
    public const MAX_ROWS = 10000;

    public function __construct(private readonly MedicineCatalogImportStager $stager) {}

    public function stage(UploadedFile $file, ?int $userId = null)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new RuntimeException('Chỉ hỗ trợ tệp XLSX hoặc CSV.');
        }

        $rows = (new FastExcel)->import($file->getRealPath())->values();
        if ($rows->isEmpty()) {
            throw new RuntimeException('Tệp không có dữ liệu để import.');
        }

        if ($rows->count() > self::MAX_ROWS) {
            throw new RuntimeException('Tệp vượt quá giới hạn '.self::MAX_ROWS.' dòng.');
        }

        return $this->stager->stage(
            $rows->map(fn ($row) => is_array($row) ? $row : $row->toArray())->all(),
            $file->getClientOriginalName(),
            $userId,
        );
    }
}

<?php

namespace Modules\Pharma\Services;

/**
 * Adapter tương thích cho các caller cũ. Luồng chính nằm ở MedicineImportExport.
 */
class MedicineImportService
{
    public function __construct(private readonly MedicineImportExport $importExport) {}

    public function importFromCsv(string $filePath): int
    {
        $report = $this->importExport->import($filePath, [
            'mode' => 'update_or_create',
            'dry_run' => false,
        ]);

        return (int) ($report['success_rows'] ?? 0);
    }
}

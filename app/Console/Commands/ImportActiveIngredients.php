<?php

namespace App\Console\Commands;

use App\Models\ActiveIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class ImportActiveIngredients extends Command
{
    protected $signature = 'pharma:import-active-ingredients
        {file : Relative or absolute path to the CSV/XLSX file}
        {--truncate : Remove all existing active ingredients before import}
        {--source= : Custom source label stored in the source_file column}';

    protected $description = 'Import active ingredients from a CSV or XLSX source file';

    public function handle(): int
    {
        $filePath = $this->resolveFilePath($this->argument('file'));

        if (! is_file($filePath)) {
            $this->error("Khong tim thay file import: {$filePath}");

            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            DB::table('active_ingredients')->truncate();
            $this->warn('Da truncate bang active_ingredients truoc khi import.');
        }

        $source = $this->option('source') ?: basename($filePath);
        $imported = 0;
        $skipped = 0;

        try {
            (new FastExcel())->import($filePath, function (array $row) use (&$imported, &$skipped, $source): void {
                $payload = ActiveIngredient::sanitizePayload($this->mapRow($row, $source));

                if (blank($payload['name'])) {
                    $skipped++;

                    return;
                }

                $record = ActiveIngredient::withTrashed()->firstOrNew([
                    'row_hash' => $payload['row_hash'],
                ]);

                $record->fill($payload);
                $record->deleted_at = null;
                $record->save();

                $imported++;
            });
        } catch (Throwable $exception) {
            $this->error('Import that bai: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Import hoan tat. Imported: {$imported}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    protected function mapRow(array $row, string $source): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[strtolower(trim((string) $key))] = $value;
        }

        return [
            'stt' => $normalized['stt'] ?? $row[0] ?? null,
            'name' => $normalized['name'] ?? $row[1] ?? null,
            'dosage_form' => $normalized['dosage_form'] ?? $row[2] ?? null,
            'hospital_level' => $normalized['hospital_level'] ?? $row[3] ?? null,
            'note' => $normalized['note'] ?? $row[4] ?? null,
            'drug_group' => $normalized['drug_group'] ?? $row[5] ?? null,
            'source_file' => $source,
        ];
    }

    protected function resolveFilePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        return base_path($path);
    }
}

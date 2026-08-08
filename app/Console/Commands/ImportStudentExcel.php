<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ntd\Models\Application;
use Modules\Ntd\Models\Student;
use Modules\Ntd\Models\Guardian;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Carbon\Carbon;

class ImportStudentExcel extends Command
{
    protected $signature = 'import:students {file}';
    protected $description = 'Import students using FastExcel';

    public function handle()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('guardians')->truncate();
        DB::table('students')->truncate();
        DB::table('applications')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $file = $this->argument('file');

        $this->info("🚀 Importing file: {$file}");

        $rows = (new FastExcel)->withoutHeaders()->import($file);


        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach ($rows as $index => $row) {

            // ⚠️ Skip 2 dòng header
            if ($index < 2) {
                $bar->advance();
                continue;
            }

            // ⚠️ Skip dòng rỗng (không có tên học sinh)
            if (empty($row[2])) {
                $bar->advance();
                continue;
            }

            DB::beginTransaction();

            try {

                // =========================
                // 1. Application
                // =========================
                $application = Application::create([
                    'code' => 'MHS_' . (
                        !empty(trim($row[1] ?? ''))
                        ? trim($row[1])
                        : str_pad($index + 1, 4, '0', STR_PAD_LEFT)
                    ),
                    'school_year' => '2026-2027',
                    'status' => 'draft',

                    'addresses' => $this->mapAddresses($row),
                    'registration' => $this->mapRegistration($row),

                    // default JSON
                    'siblings' => [],
                    'abilities' => [],
                    'health_info' => [],
                    'family_info' => [],
                    'commitment' => [],
                ]);

                // =========================
                // 2. Student
                // =========================
                Student::create([
                    'application_id' => $application->id,

                    'full_name' => $row[2] ?? null,
                    'date_of_birth' => $this->parseDate($row[3] ?? null),
                    'gender' => $this->mapGender($row[4] ?? null),

                    'ethnicity' => $row[5] ?? null,
                    'personal_id' => $row[6] ?? null,
                    'religion' => $row[7] ?? null,
                    'nationality' => $row[8] ?? null,

                    'place_of_birth' => $row[14] ?? null,
                    'birth_certificate_place' => trim(($row[15] ?? '') . ' ' . ($row[16] ?? '')),
                    'hometown' => $row[11] ?? null,

                    'phone' => $row[30] ?? null,
                ]);

                // =========================
                // 3. Guardians
                // =========================
                $this->createGuardian($application->id, 'father', $row, 32, 34, 33, 35, 36);
                $this->createGuardian($application->id, 'mother', $row, 37, 39, 38, 40, 41);
                $this->createGuardian($application->id, 'guardian', $row, 42, null, null, 45, 46);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->error("\n❌ Row " . ($index + 1) . ": " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info("\n✅ Import completed!");
    }

    // =========================
    // Helpers
    // =========================

    private function mapGender($value)
    {
        return match ((string)$value) {
            '0' => 'male',
            '1' => 'female',
            default => null
        };
    }

    private function parseDate($value)
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapAddresses($row)
    {
        return [
            'permanent' => [
                'house_number' => $row[18] ?? null,
                'street' => $row[19] ?? null,
                'district' => $row[20] ?? null,
                'ward' => $row[21] ?? null,
                'province' => $row[22] ?? null,
            ],
            'current' => [
                'house_number' => $row[24] ?? null,
                'street' => $row[25] ?? null,
                'district' => $row[26] ?? null,
                'ward' => $row[27] ?? null,
                'province' => $row[28] ?? null,
            ],
        ];
    }

    private function mapRegistration($row)
    {
        $value = strtolower($row[48] ?? '');

        return [
            'class_type' => match (true) {
                str_contains($value, 'tăng cường') => 'enhanced_english',
                str_contains($value, 'tích hợp') => 'integrated_english',
                default => 'standard',
            }
        ];
    }

    private function createGuardian($applicationId, $type, $row, $name, $birth, $job, $phone, $id)
    {
        if (empty($row[$name])) return;

        Guardian::create([
            'application_id' => $applicationId,
            'type' => $type,
            'full_name' => $row[$name] ?? null,
            'birth_year' => $row[$birth] ?? null,
            'job' => $row[$job] ?? null,
            'phone' => $row[$phone] ?? null,
            'personal_id' => $row[$id] ?? null,
        ]);
    }
}

<?php

namespace Modules\Admission\Imports;

use App\Services\Data\DataTransformer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\AdmissionImportError;
use Modules\Admission\Models\AdmissionImportRun;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ApplicationsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $totalRows = 0;
    private int $successRows = 0;
    private int $failedRows = 0;
    private int $createdRows = 0;
    private int $updatedRows = 0;

    private DataTransformer $transformer;

    public function __construct(private readonly AdmissionImportRun $run)
    {
        $this->transformer = app(DataTransformer::class);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->totalRows++;
            $rowNumber = $index + 2;
            $normalized = $this->normalizeRow($row->toArray());

            try {
                $this->processRow($normalized);
                $this->successRows++;
            } catch (AdmissionImportRowException $e) {
                $this->failedRows++;
                $this->recordError($rowNumber, $e->errorCode, $e->field, $e->getMessage(), $normalized);
            } catch (Throwable $e) {
                $this->failedRows++;
                $this->recordError($rowNumber, 'persistence_failed', null, 'Không thể lưu dòng dữ liệu này.', $normalized);

                Log::error('Admission import row failed.', [
                    'import_run_id' => $this->run->id,
                    'row' => $rowNumber,
                    'error_code' => 'persistence_failed',
                    'exception' => $e::class,
                ]);
            }
        }
    }

    private function processRow(array $row): void
    {
        $maDinhDanh = $this->cleanString($row['ma_dinh_danh'] ?? null);
        $mhs = $this->cleanString($row['mhs'] ?? null);

        if ($maDinhDanh === null && $mhs === null) {
            throw new AdmissionImportRowException('missing_identity', null, 'Thiếu cả mã định danh và mã hồ sơ.');
        }

        if ($maDinhDanh !== null && ! preg_match('/^\d{12}$/', $maDinhDanh)) {
            throw new AdmissionImportRowException('invalid_ma_dinh_danh', 'ma_dinh_danh', 'Mã định danh phải gồm đúng 12 chữ số.');
        }

        if ($mhs !== null && mb_strlen($mhs) > 100) {
            throw new AdmissionImportRowException('validation_failed', 'mhs', 'Mã hồ sơ vượt quá 100 ký tự.');
        }

        if (array_key_exists('ngay_sinh', $row) && $row['ngay_sinh'] !== null && $row['ngay_sinh'] !== '') {
            $row['ngay_sinh'] = $this->normalizeDate($row['ngay_sinh']);
        }

        $row['ma_dinh_danh'] = $maDinhDanh;
        $row['mhs'] = $mhs;

        $model = new AdmissionApplication();
        $allowed = array_flip($model->getFillable());
        $data = array_intersect_key($row, $allowed);

        foreach ([
            'approved_at', 'approved_by', 'rejected_at', 'rejected_by',
            'pdf_path', 'word_path', 'status',
        ] as $protectedField) {
            unset($data[$protectedField]);
        }

        $data = $this->transformer->transformInput($model, $data);
        $record = $this->resolveRecord($maDinhDanh, $mhs);

        DB::transaction(function () use ($record, $data): void {
            if ($record) {
                // Preserve lifecycle status and avoid approval/reset model hooks during spreadsheet data correction.
                AdmissionApplication::query()->whereKey($record->id)->update($data);
                $this->updatedRows++;

                return;
            }

            $data['status'] = 'pending';
            AdmissionApplication::query()->create($data);
            $this->createdRows++;
        });
    }

    private function resolveRecord(?string $maDinhDanh, ?string $mhs): ?AdmissionApplication
    {
        $byIdentity = collect();
        $byCode = collect();

        if ($maDinhDanh !== null) {
            $byIdentity = AdmissionApplication::query()
                ->where('ma_dinh_danh', $maDinhDanh)
                ->limit(2)
                ->get();

            if ($byIdentity->count() > 1) {
                throw new AdmissionImportRowException('ambiguous_identity', 'ma_dinh_danh', 'Mã định danh đang trùng nhiều hồ sơ trong hệ thống.');
            }
        }

        if ($mhs !== null) {
            $byCode = AdmissionApplication::query()
                ->where('mhs', $mhs)
                ->limit(2)
                ->get();

            if ($byCode->count() > 1) {
                throw new AdmissionImportRowException('ambiguous_identity', 'mhs', 'Mã hồ sơ đang trùng nhiều hồ sơ trong hệ thống.');
            }
        }

        $identityRecord = $byIdentity->first();
        $codeRecord = $byCode->first();

        if ($identityRecord && $codeRecord && $identityRecord->id !== $codeRecord->id) {
            throw new AdmissionImportRowException('identity_conflict', null, 'Mã định danh và mã hồ sơ đang trỏ tới hai hồ sơ khác nhau.');
        }

        return $identityRecord ?: $codeRecord;
    }

    private function normalizeDate(mixed $value): string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            }

            $text = trim((string) $value);

            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $text);
                    if ($date && $date->format($format) === $text) {
                        return $date->format('Y-m-d');
                    }
                } catch (Throwable) {
                    // Try the next supported format.
                }
            }
        } catch (Throwable) {
            // Converted below to a stable row error.
        }

        throw new AdmissionImportRowException('invalid_date', 'ngay_sinh', 'Ngày sinh không hợp lệ. Dùng ngày Excel hoặc định dạng dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd.');
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $column = Str::of((string) $key)
                ->trim()
                ->lower()
                ->replace([' ', '-', '.'], '_')
                ->toString();

            $normalized[$column] = $value;
        }

        return $normalized;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function recordError(int $rowNumber, string $errorCode, ?string $field, string $message, array $row): void
    {
        AdmissionImportError::query()->create([
            'import_run_id' => $this->run->id,
            'row_number' => $rowNumber,
            'error_code' => $errorCode,
            'field' => $field,
            'error_message' => $message,
            'ma_dinh_danh' => $this->cleanString($row['ma_dinh_danh'] ?? null),
            'mhs' => $this->cleanString($row['mhs'] ?? null),
            'student_name' => $this->cleanString($row['ho_va_ten_hoc_sinh'] ?? null),
            'row_snapshot' => array_filter([
                'ma_dinh_danh' => $this->cleanString($row['ma_dinh_danh'] ?? null),
                'mhs' => $this->cleanString($row['mhs'] ?? null),
                'ho_va_ten_hoc_sinh' => $this->cleanString($row['ho_va_ten_hoc_sinh'] ?? null),
                'ngay_sinh' => $field === 'ngay_sinh' ? ($row['ngay_sinh'] ?? null) : null,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
        ]);

        Log::warning('Admission import row skipped.', [
            'import_run_id' => $this->run->id,
            'row' => $rowNumber,
            'error_code' => $errorCode,
            'field' => $field,
        ]);
    }

    public function totalRows(): int { return $this->totalRows; }
    public function successRows(): int { return $this->successRows; }
    public function failedRows(): int { return $this->failedRows; }
    public function createdRows(): int { return $this->createdRows; }
    public function updatedRows(): int { return $this->updatedRows; }
}

class AdmissionImportRowException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly ?string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}

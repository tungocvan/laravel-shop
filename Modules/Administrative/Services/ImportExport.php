<?php

namespace Modules\Administrative\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'ho_so_hanh_chinh';

    protected array $requiredHeaders = [
        'procedure_code',
        'applicant_name',
        'phone',
        'student_name',
    ];

    protected array $rules = [
        'procedure_code' => ['required', 'string', 'max:50'],
        'submission_code' => ['nullable', 'string', 'max:32'],
        'applicant_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
        'student_name' => ['required', 'string', 'max:255'],
        'student_code' => ['nullable', 'string', 'max:100'],
        'date_of_birth' => ['nullable', 'date'],
        'current_class' => ['nullable', 'string', 'max:100'],
        'academic_year' => ['nullable', 'string', 'max:20'],
        'relationship' => ['nullable', 'string', 'max:50'],
        'status' => ['nullable', 'in:pending,approved,rejected,need_supplement'],
        'submitted_at' => ['nullable', 'date'],
    ];

    protected array $uniqueBy = ['submission_code'];

    protected string $mode = 'update_or_create';

    protected function modelClass(): string
    {
        return AdministrativeSubmission::class;
    }

    protected function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = trim($value);
            }
        }

        $row['procedure_code'] = Str::upper((string) ($row['procedure_code'] ?? ''));
        $row['submission_code'] = $this->nullable($row['submission_code'] ?? null);
        $row['email'] = $this->nullable($row['email'] ?? null);
        $row['student_code'] = $this->nullable($row['student_code'] ?? null);
        $row['date_of_birth'] = $this->nullable($row['date_of_birth'] ?? null);
        $row['current_class'] = $this->nullable($row['current_class'] ?? null);
        $row['academic_year'] = $this->nullable($row['academic_year'] ?? null);
        $row['relationship'] = $this->nullable($row['relationship'] ?? null);
        $row['status'] = $this->nullable($row['status'] ?? null) ?? SubmissionStatus::Pending->value;
        $row['submitted_at'] = $this->nullable($row['submitted_at'] ?? null) ?? now()->toDateTimeString();

        return $row;
    }

    protected function beforePersist(array $data, array $row, int $rowNumber, string $sheet): array
    {
        $procedure = AdministrativeProcedure::query()
            ->where('code', $data['procedure_code'])
            ->where('is_active', true)
            ->first();

        if (! $procedure) {
            throw ValidationException::withMessages([
                'procedure_code' => "Không tìm thấy thủ tục đang hoạt động có mã {$data['procedure_code']}.",
            ]);
        }

        unset($data['procedure_code']);

        $data['procedure_id'] = $procedure->id;
        $data['lookup_token_hash'] = Hash::make(Str::random(40));
        $data['wants_email_receipt'] = false;
        $data['version'] = 1;
        $data['revision_count'] = 0;

        if (empty($data['submission_code'])) {
            $data['submission_code'] = 'IMP-'.now()->format('ymd').'-'.Str::upper(Str::random(10));
        }

        return $data;
    }

    protected function exportRows(array $filters = []): Collection
    {
        $query = AdministrativeSubmission::query()->with(['procedure:id,code,name', 'processor:id,name']);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $procedureId = $filters['procedure_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('submission_code', 'like', "%{$search}%")
                    ->orWhere('applicant_name', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(in_array($status, array_column(SubmissionStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->when($procedureId, fn ($query) => $query->where('procedure_id', $procedureId))
            ->when($dateFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('submitted_at', '<=', $dateTo));

        return $query->latest('submitted_at')->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var AdministrativeSubmission $model */
        return [
            'procedure_code' => $model->procedure?->code,
            'procedure_name' => $model->procedure?->name,
            'submission_code' => $model->submission_code,
            'applicant_name' => $model->applicant_name,
            'phone' => $model->phone,
            'email' => $model->email,
            'student_name' => $model->student_name,
            'student_code' => $model->student_code,
            'date_of_birth' => $model->date_of_birth?->format('Y-m-d'),
            'current_class' => $model->current_class,
            'academic_year' => $model->academic_year,
            'relationship' => $model->relationship,
            'status' => $model->status->value,
            'submitted_at' => $model->submitted_at?->format('Y-m-d H:i:s'),
            'processed_by' => $model->processor?->name,
            'processed_at' => $model->processed_at?->format('Y-m-d H:i:s'),
            'response' => $model->response,
            'rejection_reason' => $model->rejection_reason,
            'supplement_reason' => $model->supplement_reason,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'procedure_code' => 'HC-001',
            'submission_code' => '',
            'applicant_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'email' => 'demo@example.com',
            'student_name' => 'Nguyễn Văn B',
            'student_code' => 'HS001',
            'date_of_birth' => '2012-01-15',
            'current_class' => '8A1',
            'academic_year' => '2026-2027',
            'relationship' => 'Cha',
            'status' => 'pending',
            'submitted_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

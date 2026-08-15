<?php

namespace Modules\Administrative\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\HistoryActorType;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected string $defaultSheetName = 'ho_so_hanh_chinh';

    protected array $requiredHeaders = [
        'submission_code', 'procedure_code', 'lookup_token', 'applicant_name',
        'phone', 'student_name', 'status', 'submitted_at',
    ];

    protected array $headerAliases = [
        'submission_code' => ['ma_ho_so', 'mã hồ sơ'],
        'procedure_code' => ['ma_thu_tuc', 'mã thủ tục'],
        'lookup_token' => ['ma_tra_cuu', 'mã tra cứu'],
        'applicant_name' => ['nguoi_nop', 'người nộp'],
        'phone' => ['so_dien_thoai', 'số điện thoại'],
        'student_name' => ['hoc_sinh', 'học sinh'],
        'student_code' => ['ma_hoc_sinh', 'mã học sinh'],
        'date_of_birth' => ['ngay_sinh', 'ngày sinh'],
        'current_class' => ['lop', 'lớp'],
        'academic_year' => ['nam_hoc', 'năm học'],
        'relationship' => ['quan_he', 'quan hệ'],
        'status' => ['trang_thai', 'trạng thái'],
        'submitted_at' => ['ngay_nop', 'ngày nộp'],
        'rejection_reason' => ['ly_do_tu_choi', 'lý do từ chối'],
        'supplement_reason' => ['yeu_cau_bo_sung', 'yêu cầu bổ sung'],
    ];

    protected array $rules = [
        'submission_code' => ['required', 'string', 'max:32'],
        'procedure_code' => ['required', 'string', 'max:50'],
        'lookup_token' => ['required', 'string', 'min:8', 'max:200'],
        'applicant_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
        'wants_email_receipt' => ['nullable'],
        'student_name' => ['required', 'string', 'max:255'],
        'student_code' => ['nullable', 'string', 'max:100'],
        'date_of_birth' => ['nullable', 'date'],
        'current_class' => ['nullable', 'string', 'max:100'],
        'academic_year' => ['nullable', 'string', 'max:20'],
        'relationship' => ['nullable', 'string', 'max:50'],
        'relationship_other' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:pending,approved,rejected,need_supplement'],
        'response' => ['nullable', 'string', 'max:5000'],
        'rejection_reason_code' => ['nullable', 'string', 'max:50'],
        'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:5000'],
        'supplement_reason' => ['required_if:status,need_supplement', 'nullable', 'string', 'max:5000'],
        'submitted_at' => ['required', 'date'],
        'processed_at' => ['nullable', 'date'],
    ];

    protected array $uniqueBy = ['submission_code'];
    protected string $mode = 'update_or_create';

    protected function modelClass(): string
    {
        return AdministrativeSubmission::class;
    }

    public function import(string $filePath, array $options = []): array
    {
        if (($options['mode'] ?? $this->mode) === 'replace') {
            $this->resetReport();
            $this->addError($this->defaultSheetName, null, null,
                'Mode replace bị vô hiệu cho hồ sơ hành chính. Hãy dùng chức năng Xóa tất cả để bảo toàn soft-delete và audit.');
            return $this->report(false);
        }

        $report = parent::import($filePath, $options);
        foreach ($report['errors'] as &$error) {
            if (($error['column'] ?? null) === 'lookup_token') {
                $error['value'] = '[REDACTED]';
            }
        }
        unset($error);

        return $report;
    }

    protected function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = trim($value);
            }
        }

        $row['submission_code'] = Str::upper((string) ($row['submission_code'] ?? ''));
        $row['procedure_code'] = Str::upper((string) ($row['procedure_code'] ?? ''));
        $row['phone'] = preg_replace('/\s+/', '', (string) ($row['phone'] ?? ''));
        $row['email'] = $this->nullable(isset($row['email']) ? mb_strtolower((string) $row['email']) : null);
        $row['student_code'] = $this->nullable($row['student_code'] ?? null);
        $row['date_of_birth'] = $this->nullable($row['date_of_birth'] ?? null);
        $row['current_class'] = $this->nullable($row['current_class'] ?? null);
        $row['academic_year'] = $this->nullable($row['academic_year'] ?? null);
        $row['relationship'] = $this->nullable($row['relationship'] ?? null);
        $row['relationship_other'] = $this->nullable($row['relationship_other'] ?? null);
        $row['response'] = $this->nullable($row['response'] ?? null);
        $row['rejection_reason_code'] = $this->nullable($row['rejection_reason_code'] ?? null);
        $row['rejection_reason'] = $this->nullable($row['rejection_reason'] ?? null);
        $row['supplement_reason'] = $this->nullable($row['supplement_reason'] ?? null);
        $row['processed_at'] = $this->nullable($row['processed_at'] ?? null);
        $row['wants_email_receipt'] = $this->normalizeBoolean($row['wants_email_receipt'] ?? false);

        if (! AdministrativeProcedure::query()->where('code', $row['procedure_code'])->exists()) {
            throw ValidationException::withMessages(['procedure_code' => "Không tìm thấy thủ tục có mã {$row['procedure_code']}."]);
        }

        return $row;
    }

    protected function beforePersist(array $data, array $row, int $rowNumber, string $sheet): array
    {
        $procedure = AdministrativeProcedure::query()->where('code', $data['procedure_code'])->firstOrFail();
        $lookupToken = (string) $data['lookup_token'];
        unset($data['procedure_code'], $data['lookup_token']);

        $data['procedure_id'] = $procedure->id;
        $data['lookup_token_hash'] = Hash::make($lookupToken);

        $status = SubmissionStatus::from((string) $data['status']);
        if ($status !== SubmissionStatus::Rejected) {
            $data['rejection_reason_code'] = null;
            $data['rejection_reason'] = null;
        }
        if ($status !== SubmissionStatus::NeedSupplement) {
            $data['supplement_reason'] = null;
        }

        return $data;
    }

    protected function persistRow(array $data, string $mode): Model
    {
        $existing = AdministrativeSubmission::query()->where('submission_code', $data['submission_code'])->first();

        if ($mode === 'create_only') {
            $submission = AdministrativeSubmission::query()->create($this->newSubmissionPayload($data));
            $this->writeImportedHistory($submission, null, $submission->status);
            return $submission;
        }

        if ($mode === 'skip_duplicate' && $existing) {
            $this->skippedRows++;
            return $existing;
        }

        if (! $existing) {
            $submission = AdministrativeSubmission::query()->create($this->newSubmissionPayload($data));
            $this->writeImportedHistory($submission, null, $submission->status);
            return $submission;
        }

        $fromStatus = $existing->status;
        $payload = $data;
        $payload['version'] = $existing->version + 1;
        $payload['revision_count'] = $existing->revision_count;
        $this->applyProcessingMetadata($payload);
        $existing->update($payload);
        $fresh = $existing->fresh();

        if ($fromStatus !== $fresh->status) {
            $this->writeImportedHistory($fresh, $fromStatus, $fresh->status);
        }

        return $fresh;
    }

    protected function exportRows(array $filters = []): Collection
    {
        $query = AdministrativeSubmission::query()->with(['procedure:id,code,name', 'processor:id,name']);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $procedureId = $filters['procedure_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
            $nested->where('submission_code', 'like', "%{$search}%")
                ->orWhere('applicant_name', 'like', "%{$search}%")
                ->orWhere('student_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }))->when(in_array($status, array_column(SubmissionStatus::cases(), 'value'), true), fn ($query) => $query->where('status', $status))
            ->when($procedureId, fn ($query) => $query->where('procedure_id', $procedureId))
            ->when($dateFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('submitted_at', '<=', $dateTo));

        return $query->latest('submitted_at')->limit(5000)->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var AdministrativeSubmission $model */
        return [
            'submission_code' => $model->submission_code,
            'procedure_code' => $model->procedure?->code,
            'procedure_name' => $model->procedure?->name,
            'applicant_name' => $model->applicant_name,
            'phone' => $model->phone,
            'email' => $model->email,
            'wants_email_receipt' => $model->wants_email_receipt ? 1 : 0,
            'student_name' => $model->student_name,
            'student_code' => $model->student_code,
            'date_of_birth' => $model->date_of_birth?->format('Y-m-d'),
            'current_class' => $model->current_class,
            'academic_year' => $model->academic_year,
            'relationship' => $model->relationship,
            'relationship_other' => $model->relationship_other,
            'status' => $model->status->value,
            'response' => $model->response,
            'rejection_reason_code' => $model->rejection_reason_code,
            'rejection_reason' => $model->rejection_reason,
            'supplement_reason' => $model->supplement_reason,
            'submitted_at' => $model->submitted_at?->format('Y-m-d H:i:s'),
            'processed_by_name' => $model->processor?->name,
            'processed_at' => $model->processed_at?->format('Y-m-d H:i:s'),
            'revision_count' => $model->revision_count,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'submission_code' => 'HC-DEMO-0001', 'procedure_code' => 'HC-001',
            'lookup_token' => 'DEMO-LOOKUP-2026', 'applicant_name' => 'Nguyễn Văn A',
            'phone' => '0901234567', 'email' => 'demo@example.com', 'wants_email_receipt' => 1,
            'student_name' => 'Nguyễn Văn B', 'student_code' => 'HS001', 'date_of_birth' => '2012-01-15',
            'current_class' => '8A1', 'academic_year' => '2026-2027', 'relationship' => 'Cha',
            'relationship_other' => '', 'status' => 'pending', 'response' => '',
            'rejection_reason_code' => '', 'rejection_reason' => '', 'supplement_reason' => '',
            'submitted_at' => now()->format('Y-m-d H:i:s'), 'processed_at' => '',
        ];
    }

    private function newSubmissionPayload(array $data): array
    {
        $payload = $data + ['version' => 1, 'revision_count' => 0];
        $this->applyProcessingMetadata($payload);
        return $payload;
    }

    private function applyProcessingMetadata(array &$payload): void
    {
        $status = SubmissionStatus::from((string) $payload['status']);
        if ($status === SubmissionStatus::Pending) {
            $payload['processed_by'] = null;
            $payload['processed_at'] = null;
            return;
        }

        $payload['processed_by'] = auth('admin')->id();
        $payload['processed_at'] = $payload['processed_at'] ?? now();
    }

    private function writeImportedHistory(AdministrativeSubmission $submission, ?SubmissionStatus $from, SubmissionStatus $to): void
    {
        $metadata = ['source' => 'administrative_import'];

        if ($from === null && $to !== SubmissionStatus::Pending) {
            $submission->statusHistories()->create([
                'from_status' => null, 'to_status' => SubmissionStatus::Pending,
                'action' => SubmissionAction::Submitted, 'actor_type' => HistoryActorType::Admin,
                'actor_id' => auth('admin')->id(), 'metadata' => $metadata,
            ]);
            $from = SubmissionStatus::Pending;
        }

        $action = match ($to) {
            SubmissionStatus::Pending => SubmissionAction::Submitted,
            SubmissionStatus::Approved => SubmissionAction::Approved,
            SubmissionStatus::Rejected => SubmissionAction::Rejected,
            SubmissionStatus::NeedSupplement => SubmissionAction::SupplementRequested,
        };

        $submission->statusHistories()->create([
            'from_status' => $from, 'to_status' => $to, 'action' => $action,
            'actor_type' => HistoryActorType::Admin, 'actor_id' => auth('admin')->id(),
            'note' => $submission->response, 'reason_code' => $submission->rejection_reason_code,
            'reason' => $to === SubmissionStatus::NeedSupplement ? $submission->supplement_reason : $submission->rejection_reason,
            'metadata' => $metadata,
        ]);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'co', 'có'], true);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

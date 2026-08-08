<?php

namespace Modules\Administrative\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\AdministrativeFileType;
use Modules\Administrative\Enums\HistoryActorType;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;
use Throwable;

class SubmissionService
{
    public function __construct(private readonly AdministrativeFileService $files) {}

    public function submit(AdministrativeProcedure $procedure, array $data, array $uploads): array
    {
        abort_unless($procedure->is_active && ! $procedure->trashed(), 404);
        $rateKey = 'administrative-submit:'.sha1(request()->ip().'|'.preg_replace('/\D+/', '', (string) $data['phone']));
        $ipRateKey = 'administrative-submit-ip:'.sha1((string) request()->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5) || RateLimiter::tooManyAttempts($ipRateKey, 20)) {
            throw ValidationException::withMessages(['form' => 'Bạn đã gửi quá nhiều lần. Vui lòng thử lại sau.']);
        }
        RateLimiter::hit($rateKey, 60);
        RateLimiter::hit($ipRateKey, 60);
        $this->files->validateUploads($procedure, $uploads);
        $storedFiles = $this->files->storeTemporary($uploads);
        $lookupToken = Str::upper(Str::random(8).'-'.Str::random(8));

        try {
            $submission = DB::transaction(function () use ($procedure, $data, $storedFiles, $lookupToken): AdministrativeSubmission {
                $submittedAt = now();
                $submission = AdministrativeSubmission::query()->create([
                    'procedure_id' => $procedure->id,
                    // 4 ký tự tiền tố + ULID 26 ký tự, luôn nằm trong varchar(32).
                    'submission_code' => $this->temporarySubmissionCode(),
                    'lookup_token_hash' => Hash::make($lookupToken),
                    'applicant_name' => trim($data['applicant_name']),
                    'phone' => preg_replace('/\s+/', '', $data['phone']),
                    'email' => $this->nullable($data['email'] ?? null),
                    'wants_email_receipt' => (bool) ($data['wants_email_receipt'] ?? false),
                    'student_name' => trim($data['student_name']),
                    'student_code' => $this->nullable($data['student_code'] ?? null),
                    'date_of_birth' => $data['date_of_birth'] ?: null,
                    'current_class' => $this->nullable($data['current_class'] ?? null),
                    'academic_year' => $this->nullable($data['academic_year'] ?? null),
                    'relationship' => $this->nullable($data['relationship'] ?? null),
                    'relationship_other' => $this->nullable($data['relationship_other'] ?? null),
                    'status' => SubmissionStatus::Pending,
                    'submitted_at' => $submittedAt,
                ]);

                $submission->update(['submission_code' => $this->formatSubmissionCode($submission->id, $submittedAt)]);
                $this->files->attach($submission, $storedFiles);
                $submission->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => SubmissionStatus::Pending,
                    'action' => SubmissionAction::Submitted,
                    'actor_type' => HistoryActorType::PublicUser,
                    'metadata' => ['privacy_consent' => true],
                ]);

                return $submission->refresh();
            });
        } catch (Throwable $exception) {
            $this->files->cleanup($storedFiles);
            throw $exception;
        }

        return ['submission' => $submission, 'lookup_token' => $lookupToken];
    }

    public function findForReceipt(int $id): AdministrativeSubmission
    {
        return AdministrativeSubmission::query()->with('procedure:id,name')->findOrFail($id);
    }

    public function listForAdmin(array $filters, string|int $perPage = 10): LengthAwarePaginator|Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $procedureId = $filters['procedure_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $query = AdministrativeSubmission::query()
            ->select(['id', 'procedure_id', 'submission_code', 'applicant_name', 'student_name', 'phone', 'email', 'status', 'submitted_at', 'processed_by', 'processed_at'])
            ->with(['procedure:id,code,name', 'processor:id,name'])
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
            ->when($dateTo, fn ($query) => $query->whereDate('submitted_at', '<=', $dateTo))
            ->latest('submitted_at');

        return $perPage === 'All' ? $query->get() : $query->paginate((int) $perPage);
    }

    public function adminStats(): array
    {
        $counts = AdministrativeSubmission::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts[SubmissionStatus::Pending->value] ?? 0),
            'approved' => (int) ($counts[SubmissionStatus::Approved->value] ?? 0),
            'rejected' => (int) ($counts[SubmissionStatus::Rejected->value] ?? 0),
            'need_supplement' => (int) ($counts[SubmissionStatus::NeedSupplement->value] ?? 0),
        ];
    }

    public function procedureOptions(): Collection
    {
        return AdministrativeProcedure::query()->select(['id', 'code', 'name'])->ordered()->get();
    }

    public function findForAdmin(int $id): AdministrativeSubmission
    {
        return AdministrativeSubmission::query()->with([
            'procedure:id,code,name',
            'processor:id,name',
            'files:id,submission_id,file_type,original_name,mime_type,size,created_at',
            'statusHistories.actorAdmin:id,name',
        ])->findOrFail($id);
    }

    public function softDeleteMany(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return 0;
        }

        return DB::transaction(function () use ($ids): int {
            $submissions = AdministrativeSubmission::query()->whereKey($ids)->lockForUpdate()->get();
            $submissions->each->delete();

            return $submissions->count();
        });
    }

    public function approve(int $id, int $expectedVersion, int $adminId, ?string $response): AdministrativeSubmission
    {
        return $this->process($id, $expectedVersion, $adminId, SubmissionStatus::Approved, $response, null, null);
    }

    public function reject(int $id, int $expectedVersion, int $adminId, string $reasonCode, string $reason, ?string $response): AdministrativeSubmission
    {
        return $this->process($id, $expectedVersion, $adminId, SubmissionStatus::Rejected, $response, $reasonCode, $reason);
    }

    public function requestSupplement(int $id, int $expectedVersion, int $adminId, string $reason, ?string $response): AdministrativeSubmission
    {
        return DB::transaction(function () use ($id, $expectedVersion, $adminId, $reason, $response): AdministrativeSubmission {
            $submission = AdministrativeSubmission::query()->lockForUpdate()->findOrFail($id);
            $this->ensurePendingVersion($submission, $expectedVersion);
            $fromStatus = $submission->status;
            $submission->update([
                'status' => SubmissionStatus::NeedSupplement,
                'response' => $this->nullable($response),
                'supplement_reason' => trim($reason),
                'supplement_requested_at' => now(),
                'processed_by' => $adminId,
                'processed_at' => now(),
                'version' => $submission->version + 1,
            ]);
            $submission->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => SubmissionStatus::NeedSupplement,
                'action' => SubmissionAction::SupplementRequested,
                'actor_type' => HistoryActorType::Admin,
                'actor_id' => $adminId,
                'note' => $this->nullable($response),
                'reason' => trim($reason),
            ]);

            return $submission->refresh();
        });
    }

    public function resubmitSupplement(int $id, int $expectedVersion, array $uploads): AdministrativeSubmission
    {
        $submission = AdministrativeSubmission::query()->with('procedure')->findOrFail($id);
        abort_unless($submission->status === SubmissionStatus::NeedSupplement, 404);
        $rateKey = 'administrative-supplement:'.sha1(request()->ip().'|'.$submission->submission_code);
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages(['files' => 'Bạn đã gửi bổ sung quá nhiều lần. Vui lòng thử lại sau.']);
        }
        RateLimiter::hit($rateKey, 300);
        $this->files->validateUploads($submission->procedure, $uploads);
        $storedFiles = $this->files->storeTemporary($uploads);

        try {
            return DB::transaction(function () use ($id, $expectedVersion, $storedFiles): AdministrativeSubmission {
                $locked = AdministrativeSubmission::query()->lockForUpdate()->findOrFail($id);
                if ($locked->status !== SubmissionStatus::NeedSupplement || $locked->version !== $expectedVersion) {
                    throw ValidationException::withMessages(['files' => 'Hồ sơ đã thay đổi hoặc đã được gửi lại. Vui lòng tải lại trang.']);
                }
                $fromStatus = $locked->status;
                $this->files->attach($locked, $storedFiles, AdministrativeFileType::Supplement);
                $locked->update([
                    'status' => SubmissionStatus::Pending,
                    'processed_by' => null,
                    'processed_at' => null,
                    'supplement_reason' => null,
                    'resubmitted_at' => now(),
                    'revision_count' => $locked->revision_count + 1,
                    'version' => $locked->version + 1,
                ]);
                $locked->statusHistories()->create([
                    'from_status' => $fromStatus,
                    'to_status' => SubmissionStatus::Pending,
                    'action' => SubmissionAction::Resubmitted,
                    'actor_type' => HistoryActorType::PublicUser,
                    'reason' => 'Phụ huynh đã gửi file bổ sung.',
                    'metadata' => ['file_count' => count($storedFiles), 'revision' => $locked->revision_count],
                ]);

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            $this->files->cleanup($storedFiles);
            throw $exception;
        }
    }

    private function process(int $id, int $expectedVersion, int $adminId, SubmissionStatus $target, ?string $response, ?string $reasonCode, ?string $reason): AdministrativeSubmission
    {
        return DB::transaction(function () use ($id, $expectedVersion, $adminId, $target, $response, $reasonCode, $reason): AdministrativeSubmission {
            $submission = AdministrativeSubmission::query()->lockForUpdate()->findOrFail($id);

            $this->ensurePendingVersion($submission, $expectedVersion);

            $fromStatus = $submission->status;
            $submission->update([
                'status' => $target,
                'response' => $this->nullable($response),
                'rejection_reason_code' => $target === SubmissionStatus::Rejected ? $reasonCode : null,
                'rejection_reason' => $target === SubmissionStatus::Rejected ? $reason : null,
                'processed_by' => $adminId,
                'processed_at' => now(),
                'version' => $submission->version + 1,
            ]);
            $submission->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $target,
                'action' => $target === SubmissionStatus::Approved ? SubmissionAction::Approved : SubmissionAction::Rejected,
                'actor_type' => HistoryActorType::Admin,
                'actor_id' => $adminId,
                'note' => $this->nullable($response),
                'reason_code' => $reasonCode,
                'reason' => $reason,
            ]);

            return $submission->refresh();
        });
    }

    private function ensurePendingVersion(AdministrativeSubmission $submission, int $expectedVersion): void
    {
        if ($submission->status !== SubmissionStatus::Pending || $submission->version !== $expectedVersion) {
            throw ValidationException::withMessages(['processing' => 'Hồ sơ đã được người khác xử lý hoặc dữ liệu đã thay đổi. Vui lòng tải lại trang.']);
        }
    }

    public function formatSubmissionCode(int $id, \DateTimeInterface $submittedAt): string
    {
        return 'HC-'.$submittedAt->format('Ymd').'-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    public function temporarySubmissionCode(): string
    {
        return 'TMP-'.Str::ulid();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

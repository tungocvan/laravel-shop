<?php

namespace Modules\Administrative\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeProcedure;
use Modules\Administrative\Models\AdministrativeSubmission;

class SubmissionQueryService
{
    private const ADMIN_PAGE_SIZES = [10, 25, 50, 100];

    public function listForAdmin(array $filters, string|int $perPage = 10): LengthAwarePaginator
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

        return $query->paginate($this->normalizeAdminPageSize($perPage));
    }

    public function adminStats(): array
    {
        $counts = AdministrativeSubmission::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

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
        return AdministrativeProcedure::query()
            ->select(['id', 'code', 'name'])
            ->ordered()
            ->get();
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

    private function normalizeAdminPageSize(string|int $perPage): int
    {
        $perPage = (int) $perPage;

        return in_array($perPage, self::ADMIN_PAGE_SIZES, true) ? $perPage : 10;
    }
}

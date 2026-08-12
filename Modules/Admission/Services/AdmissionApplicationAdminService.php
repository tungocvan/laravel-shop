<?php

namespace Modules\Admission\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admission\Exports\ApplicationsExport;
use Modules\Admission\Models\AdmissionApplication;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdmissionApplicationAdminService
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 50];

    public function query(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $class = (string) ($filters['class'] ?? '');

        return AdmissionApplication::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('ho_va_ten_hoc_sinh', 'like', "%{$search}%")
                        ->orWhere('ma_dinh_danh', 'like', "%{$search}%")
                        ->orWhere('sdt_enetviet', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($class !== '', fn (Builder $query) => $query->where('loai_lop_dang_ky', $class))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;

        return $this->query($filters)->paginate($perPage);
    }

    public function approve(int $id, int $adminId): bool
    {
        return DB::transaction(function () use ($id, $adminId) {
            $application = AdmissionApplication::query()->lockForUpdate()->findOrFail($id);

            if ($application->status !== 'pending') {
                return false;
            }

            $application->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminId,
                'rejected_at' => null,
                'rejected_by' => null,
            ]);

            return true;
        });
    }

    public function reject(int $id, int $adminId): bool
    {
        return DB::transaction(function () use ($id, $adminId) {
            $application = AdmissionApplication::query()->lockForUpdate()->findOrFail($id);

            if ($application->status !== 'pending') {
                return false;
            }

            $application->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $adminId,
                'approved_at' => null,
                'approved_by' => null,
            ]);

            return true;
        });
    }

    public function deleteMany(array $ids): int
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $deleted = 0;

        AdmissionApplication::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->each(function (AdmissionApplication $application) use (&$deleted) {
                // Delete one model at a time so the existing model file-cleanup hook remains intact.
                $application->delete();
                $deleted++;
            });

        return $deleted;
    }

    public function downloadExport(array $filters): BinaryFileResponse
    {
        return Excel::download(
            new ApplicationsExport(
                $filters['search'] ?? null,
                $filters['status'] ?? null,
                $filters['class'] ?? null,
            ),
            'applications.xlsx'
        );
    }
}

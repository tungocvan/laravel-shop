<?php

namespace Modules\Admission\Services;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admission\Exports\ApplicationsExport;
use Modules\Admission\Jobs\GenerateAdmissionPdfJob;
use Modules\Admission\Models\AdmissionApplication;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdmissionApplicationAdminService
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

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
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $deleted = 0;

        AdmissionApplication::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (AdmissionApplication $application) use (&$deleted) {
                if ($application->delete()) {
                    $deleted++;
                }
            });

        return $deleted;
    }

    public function deleteAllAndResetIncrement(): int
    {
        return DB::transaction(function () {
            $applications = AdmissionApplication::query()->get();
            $deleted = 0;

            foreach ($applications as $application) {
                if ($application->delete()) {
                    $deleted++;
                }
            }

            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement('ALTER TABLE `admission_applications` AUTO_INCREMENT = 1');
            } elseif ($driver === 'sqlite') {
                DB::table('sqlite_sequence')->where('name', 'admission_applications')->delete();
            }

            return $deleted;
        });
    }

    public function queueDocumentsForIds(array $ids, bool $docx = true, bool $pdf = false): ?Batch
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        return $this->queueDocumentQuery(
            AdmissionApplication::query()->whereIn('id', $ids),
            $docx,
            $pdf
        );
    }

    public function queueDocumentsForFilters(array $filters, bool $docx = true, bool $pdf = false): ?Batch
    {
        return $this->queueDocumentQuery($this->query($filters), $docx, $pdf);
    }

    public function downloadExport(array $filters): BinaryFileResponse
    {
        return Excel::download(
            new ApplicationsExport($this->query($filters)),
            'admission-applications-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function queueDocumentQuery(Builder $query, bool $docx, bool $pdf): ?Batch
    {
        if (! $docx && ! $pdf) {
            throw new RuntimeException('Phải chọn ít nhất một định dạng tài liệu.');
        }

        $jobs = $query
            ->where('status', 'approved')
            ->when($docx && ! $pdf, fn (Builder $query) => $query->whereNull('word_path'))
            ->when($pdf && ! $docx, fn (Builder $query) => $query->whereNull('pdf_path'))
            ->when($docx && $pdf, fn (Builder $query) => $query->where(function (Builder $nested) {
                $nested->whereNull('word_path')->orWhereNull('pdf_path');
            }))
            ->pluck('id')
            ->map(fn ($id) => new GenerateAdmissionPdfJob((int) $id, $docx, $pdf))
            ->all();

        if ($jobs === []) {
            return null;
        }

        return Bus::batch($jobs)
            ->name('Admission document generation')
            ->onQueue('admission-documents')
            ->dispatch();
    }
}

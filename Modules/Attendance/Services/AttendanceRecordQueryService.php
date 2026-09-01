<?php

namespace Modules\Attendance\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Attendance\Enums\AdjustmentStatus;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceRecordQueryService
{
    public function query(array $filters = [], array $selectedIds = []): Builder
    {
        $filters = $this->normalize($filters);

        $query = AttendanceRecord::query()
            ->with(['employeeProfile', 'user', 'shift', 'checkInLocation', 'checkOutLocation'])
            ->with(['adjustmentRequests' => fn ($query) => $query
                ->where('status', AdjustmentStatus::Pending->value)
                ->latest('submitted_at')]);

        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $query->where(function ($query) use ($term): void {
                $query->whereHas('employeeProfile', fn ($profile) => $profile
                    ->where('employee_code', 'like', "%{$term}%")
                    ->orWhere('department', 'like', "%{$term}%")
                    ->orWhere('position', 'like', "%{$term}%"))
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"));
            });
        }

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if ($filters['shift'] !== 'all') {
            $query->where('shift_id', (int) $filters['shift']);
        }
        if ($filters['location'] !== 'all') {
            $locationId = (int) $filters['location'];
            $query->where(fn ($query) => $query
                ->where('check_in_location_id', $locationId)
                ->orWhere('check_out_location_id', $locationId));
        }
        if ($filters['fromDate'] !== '') {
            $query->whereDate('work_date', '>=', $filters['fromDate']);
        }
        if ($filters['toDate'] !== '') {
            $query->whereDate('work_date', '<=', $filters['toDate']);
        }

        $selectedIds = $this->normalizeSelectedIds($selectedIds);
        if ($selectedIds !== []) {
            $query->whereKey($selectedIds);
        }

        return $query->orderByDesc('work_date')->orderByDesc('checked_in_at');
    }

    public function normalize(array $filters): array
    {
        $status = (string) ($filters['status'] ?? 'all');
        $validStatuses = array_column(AttendanceRecordStatus::cases(), 'value');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => in_array($status, $validStatuses, true) ? $status : 'all',
            'shift' => $this->normalizeIdFilter($filters['shift'] ?? 'all'),
            'location' => $this->normalizeIdFilter($filters['location'] ?? 'all'),
            'fromDate' => $this->normalizeDate($filters['fromDate'] ?? ''),
            'toDate' => $this->normalizeDate($filters['toDate'] ?? ''),
        ];
    }

    public function normalizeSelectedIds(array $ids): array
    {
        $normalized = array_map('intval', $ids);
        $normalized = array_values(array_unique(array_filter($normalized, fn (int $id) => $id > 0)));
        sort($normalized);

        return $normalized;
    }

    private function normalizeIdFilter(mixed $value): string
    {
        $value = (string) $value;

        return ctype_digit($value) && (int) $value > 0 ? $value : 'all';
    }

    private function normalizeDate(mixed $value): string
    {
        $value = (string) $value;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : '';
    }
}

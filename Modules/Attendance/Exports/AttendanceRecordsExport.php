<?php

namespace Modules\Attendance\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceRecordsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $recordsQuery) {}

    public function query(): Builder
    {
        return clone $this->recordsQuery;
    }

    public function headings(): array
    {
        return [
            'Mã nhân viên',
            'Nhân viên',
            'Email',
            'Phòng ban',
            'Chức danh',
            'Ngày công',
            'Mã ca',
            'Tên ca',
            'Bắt đầu ca',
            'Kết thúc ca',
            'Giờ vào',
            'Giờ ra',
            'Phút làm',
            'Phút đi trễ',
            'Phút về sớm',
            'Trạng thái',
            'Vị trí vào',
            'Vị trí ra',
        ];
    }

    /** @param AttendanceRecord $record */
    public function map($record): array
    {
        return [
            $record->employeeProfile?->employee_code ?? '',
            $record->user?->name ?? '',
            $record->user?->email ?? '',
            $record->employeeProfile?->department ?? '',
            $record->employeeProfile?->position ?? '',
            $record->work_date?->format('d/m/Y') ?? '',
            $record->shift_code_snapshot ?? '',
            $record->shift_name_snapshot ?? '',
            $record->shift_start_time_snapshot ?? '',
            $record->shift_end_time_snapshot ?? '',
            $record->checked_in_at?->format('d/m/Y H:i') ?? '',
            $record->checked_out_at?->format('d/m/Y H:i') ?? '',
            (int) $record->worked_minutes,
            (int) $record->late_minutes,
            (int) $record->early_leave_minutes,
            $this->statusLabel($record->status),
            $record->checkInLocation?->name ?? '',
            $record->checkOutLocation?->name ?? '',
        ];
    }

    private function statusLabel(AttendanceRecordStatus $status): string
    {
        return match ($status) {
            AttendanceRecordStatus::CheckedIn => 'Đã vào ca',
            AttendanceRecordStatus::Completed => 'Hoàn tất',
            AttendanceRecordStatus::Voided => 'Đã vô hiệu',
        };
    }
}

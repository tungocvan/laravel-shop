<?php

namespace Modules\Attendance\Livewire;

use Carbon\CarbonImmutable;
use DomainException;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attendance\Exports\AttendanceRecordsExport;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceShift;
use Modules\Attendance\Services\AttendanceAdjustmentService;
use Modules\Attendance\Services\AttendanceRecordMaintenanceService;
use Modules\Attendance\Services\AttendanceRecordQueryService;

class AdminRecordsTable extends Component
{
    use WithPagination;

    protected AttendanceAdjustmentService $adjustments;

    protected AttendanceRecordMaintenanceService $maintenance;

    protected AttendanceRecordQueryService $recordsQuery;

    public string $search = '';

    public string $status = 'all';

    public string $shift = 'all';

    public string $location = 'all';

    public string $fromDate = '';

    public string $toDate = '';

    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public array $selectedRecordIds = [];

    public ?int $selectedRecordId = null;

    public ?int $selectedAdjustmentId = null;

    public string $dialog = '';

    public string $voidReason = '';

    public string $correctionCheckIn = '';

    public string $correctionCheckOut = '';

    public string $correctionReason = '';

    public string $reviewNote = '';

    public ?string $notice = null;

    public ?string $error = null;

    protected $queryString = ['search', 'status', 'shift', 'location', 'fromDate', 'toDate', 'perPage'];

    public function boot(
        AttendanceAdjustmentService $adjustments,
        AttendanceRecordMaintenanceService $maintenance,
        AttendanceRecordQueryService $recordsQuery,
    ): void {
        $this->adjustments = $adjustments;
        $this->maintenance = $maintenance;
        $this->recordsQuery = $recordsQuery;
    }

    public function mount(): void
    {
        $this->perPage = $this->normalizePerPage($this->perPage);
        if ($this->fromDate === '' && $this->toDate === '') {
            $this->fromDate = now()->startOfMonth()->toDateString();
            $this->toDate = now()->toDateString();
        }
    }

    public function updatedSearch(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedStatus(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedShift(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedLocation(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedFromDate(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedToDate(): void
    {
        $this->recordsFilterChanged();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->shift = 'all';
        $this->location = 'all';
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->perPage = 10;
        $this->selectedRecordIds = [];
        $this->resetPage();
    }

    public function exportFiltered()
    {
        $this->authorizePermission('attendance.export');
        $this->clearMessages();

        return Excel::download(
            new AttendanceRecordsExport($this->recordsQuery->query($this->filters())),
            $this->exportFilename('filtered'),
        );
    }

    public function exportSelected()
    {
        $this->authorizePermission('attendance.export');
        $this->clearMessages();

        $selectedIds = $this->recordsQuery->normalizeSelectedIds($this->selectedRecordIds);
        if ($selectedIds === []) {
            $this->error = 'Vui lòng chọn ít nhất một bản ghi để xuất.';

            return null;
        }

        return Excel::download(
            new AttendanceRecordsExport($this->recordsQuery->query($this->filters(), $selectedIds)),
            $this->exportFilename('selected'),
        );
    }

    public function openVoid(int $recordId): void
    {
        $this->authorizePermission('attendance.record.void');
        $this->selectedRecordId = $recordId;
        $this->voidReason = '';
        $this->openDialog('void');
    }

    public function voidRecord(): void
    {
        $this->authorizePermission('attendance.record.void');
        $this->clearMessages();

        try {
            $record = AttendanceRecord::query()->findOrFail($this->selectedRecordId);
            $this->maintenance->void($record, (int) auth('admin')->id(), $this->voidReason);
            $this->notice = 'Đã vô hiệu hóa bản ghi chấm công và lưu audit.';
            $this->closeDialog();
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function openCorrection(int $recordId): void
    {
        $this->authorizePermission('attendance.record.adjust');
        $record = AttendanceRecord::query()->findOrFail($recordId);
        $this->selectedRecordId = $recordId;
        $this->correctionCheckIn = $record->checked_in_at?->format('Y-m-d\TH:i') ?? '';
        $this->correctionCheckOut = $record->checked_out_at?->format('Y-m-d\TH:i') ?? '';
        $this->correctionReason = '';
        $this->openDialog('correction');
    }

    public function correctRecord(): void
    {
        $this->authorizePermission('attendance.record.adjust');
        $this->clearMessages();

        try {
            if ($this->correctionCheckIn === '' || $this->correctionCheckOut === '') {
                throw new DomainException('Vui lòng nhập đầy đủ giờ vào và giờ ra.');
            }

            $record = AttendanceRecord::query()->findOrFail($this->selectedRecordId);
            $this->maintenance->correctTimes(
                $record,
                (int) auth('admin')->id(),
                CarbonImmutable::parse($this->correctionCheckIn),
                CarbonImmutable::parse($this->correctionCheckOut),
                $this->correctionReason,
            );
            $this->notice = 'Đã điều chỉnh giờ chấm công và tính lại số phút.';
            $this->closeDialog();
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function openAdjustment(int $adjustmentId): void
    {
        $this->authorizePermission('attendance.adjustment.view');
        $this->selectedAdjustmentId = $adjustmentId;
        $this->reviewNote = '';
        $this->openDialog('adjustment');
    }

    public function approveAdjustment(): void
    {
        $this->authorizePermission('attendance.adjustment.approve');
        $this->reviewAdjustment(true);
    }

    public function rejectAdjustment(): void
    {
        $this->authorizePermission('attendance.adjustment.approve');
        $this->reviewAdjustment(false);
    }

    public function closeDialog(): void
    {
        $this->dialog = '';
        $this->selectedRecordId = null;
        $this->selectedAdjustmentId = null;
    }

    public function render()
    {
        $records = $this->recordsQuery
            ->query($this->filters())
            ->paginate($this->perPage);
        $selectedAdjustment = $this->selectedAdjustmentId
            ? AttendanceAdjustmentRequest::query()->with(['employeeProfile', 'attendanceRecord'])->find($this->selectedAdjustmentId)
            : null;

        return view('Attendance::livewire.admin-records-table', [
            'records' => $records,
            'shifts' => AttendanceShift::query()->orderBy('name')->get(['id', 'name', 'code']),
            'locations' => AttendanceLocation::query()->orderBy('name')->get(['id', 'name', 'code']),
            'selectedAdjustment' => $selectedAdjustment,
        ]);
    }

    private function reviewAdjustment(bool $approve): void
    {
        $this->clearMessages();

        try {
            $request = AttendanceAdjustmentRequest::query()->findOrFail($this->selectedAdjustmentId);
            if ($approve) {
                $this->adjustments->approve($request, (int) auth('admin')->id(), $this->reviewNote ?: null);
                $this->notice = 'Đã duyệt yêu cầu điều chỉnh chấm công.';
            } else {
                $this->adjustments->reject($request, (int) auth('admin')->id(), $this->reviewNote);
                $this->notice = 'Đã từ chối yêu cầu điều chỉnh chấm công.';
            }
            $this->closeDialog();
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'shift' => $this->shift,
            'location' => $this->location,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];
    }

    private function exportFilename(string $scope): string
    {
        return sprintf('attendance-records-%s-%s.xlsx', $scope, now()->format('Ymd-His'));
    }

    private function recordsFilterChanged(): void
    {
        $this->selectedRecordIds = [];
        $this->resetPage();
    }

    private function openDialog(string $dialog): void
    {
        $this->clearMessages();
        $this->dialog = $dialog;
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->error = null;
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, $this->perPageOptions, true) ? $value : 10;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}

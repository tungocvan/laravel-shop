<?php

namespace Modules\ClientPortal\Applications\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Account\Models\EmployeeProfile;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Services\AttendanceAdjustmentService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\ShiftResolver;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class AttendanceApplicationController extends Controller
{
    public function dashboard(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        ShiftResolver $shiftResolver,
    ): View {
        [$application, $applicationPresentation, $userId] = $this->applicationContext($request, $registry, $settings);
        $employee = $this->employeeForUser($userId);
        $shift = null;
        $record = null;
        $configurationError = null;

        if ($employee) {
            try {
                $shift = $shiftResolver->resolve(CarbonImmutable::now());
                $record = AttendanceRecord::query()
                    ->with(['checkInLocation:id,name', 'checkOutLocation:id,name'])
                    ->where('user_id', $userId)
                    ->where('work_date', $shift['work_date'])
                    ->latest('checked_in_at')
                    ->first();
            } catch (DomainException $exception) {
                $configurationError = $this->attendanceErrorMessage($exception);
            }
        }

        $locations = AttendanceLocation::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('check_in_enabled', true)->orWhere('check_out_enabled', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'radius_meters']);

        return view('ClientPortal::applications.attendance.dashboard', compact(
            'application',
            'applicationPresentation',
            'employee',
            'shift',
            'record',
            'locations',
            'configurationError',
        ));
    }

    public function checkIn(Request $request, AttendanceService $attendanceService): RedirectResponse
    {
        return $this->performAttendanceAction($request, $attendanceService, 'checkIn');
    }

    public function checkOut(Request $request, AttendanceService $attendanceService): RedirectResponse
    {
        return $this->performAttendanceAction($request, $attendanceService, 'checkOut');
    }

    public function history(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
    ): View {
        [$application, $applicationPresentation, $userId] = $this->applicationContext($request, $registry, $settings);
        $employee = $this->employeeForUser($userId);

        /** @var LengthAwarePaginator $records */
        $records = AttendanceRecord::query()
            ->with(['checkInLocation:id,name', 'checkOutLocation:id,name'])
            ->where('user_id', $userId)
            ->orderByDesc('work_date')
            ->orderByDesc('checked_in_at')
            ->paginate(10)
            ->withQueryString();

        return view('ClientPortal::applications.attendance.history', compact(
            'application',
            'applicationPresentation',
            'employee',
            'records',
        ));
    }

    public function adjustments(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
    ): View {
        [$application, $applicationPresentation, $userId] = $this->applicationContext($request, $registry, $settings);
        $employee = $this->employeeForUser($userId);

        $records = AttendanceRecord::query()
            ->where('user_id', $userId)
            ->orderByDesc('work_date')
            ->limit(20)
            ->get(['id', 'work_date', 'checked_in_at', 'checked_out_at', 'status']);

        $adjustments = AttendanceAdjustmentRequest::query()
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')
            ->limit(20)
            ->get();

        return view('ClientPortal::applications.attendance.adjustments', compact(
            'application',
            'applicationPresentation',
            'employee',
            'records',
            'adjustments',
        ));
    }

    public function submitAdjustment(Request $request, AttendanceAdjustmentService $adjustmentService): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $employee = $this->employeeForUser((int) $user->getAuthIdentifier());
        if (! $employee) {
            return back()->withInput()->with('attendance_error', 'Tài khoản của bạn chưa có hồ sơ nhân viên để gửi yêu cầu điều chỉnh.');
        }

        $validated = $request->validate([
            'record_id' => ['nullable', 'integer'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'requested_check_in_at' => ['nullable', 'date'],
            'requested_check_out_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $record = null;
        if (! empty($validated['record_id'])) {
            $record = AttendanceRecord::query()
                ->whereKey((int) $validated['record_id'])
                ->where('user_id', $user->getAuthIdentifier())
                ->firstOrFail();
        }

        try {
            $adjustmentService->submit(
                $employee,
                CarbonImmutable::parse($validated['work_date']),
                ! empty($validated['requested_check_in_at']) ? CarbonImmutable::parse($validated['requested_check_in_at']) : null,
                ! empty($validated['requested_check_out_at']) ? CarbonImmutable::parse($validated['requested_check_out_at']) : null,
                $validated['reason'],
                $validated['note'] ?? null,
                $record,
            );
        } catch (DomainException $exception) {
            return back()->withInput()->with('attendance_error', $this->attendanceErrorMessage($exception));
        }

        return redirect()->route('client.attendance.adjustments')->with('attendance_success', 'Yêu cầu điều chỉnh đã được gửi.');
    }

    private function performAttendanceAction(Request $request, AttendanceService $attendanceService, string $method): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $employee = $this->employeeForUser((int) $user->getAuthIdentifier());
        if (! $employee) {
            return back()->with('attendance_error', 'Tài khoản của bạn chưa có hồ sơ nhân viên để chấm công.');
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'captured_at' => ['required', 'date'],
        ]);

        try {
            $attendanceService->{$method}(
                $employee,
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (float) $validated['accuracy'],
                CarbonImmutable::parse($validated['captured_at']),
            );
        } catch (DomainException $exception) {
            return back()->with('attendance_error', $this->attendanceErrorMessage($exception));
        }

        return redirect()->route('client.attendance.dashboard')->with(
            'attendance_success',
            $method === 'checkIn' ? 'Đã chấm công vào thành công.' : 'Đã chấm công ra thành công.',
        );
    }

    private function applicationContext(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): array
    {
        $application = $registry->find('attendance');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        return [
            $application,
            $settings->applicationPresentation($application),
            (int) $user->getAuthIdentifier(),
        ];
    }

    private function employeeForUser(int $userId): ?EmployeeProfile
    {
        return EmployeeProfile::query()->where('user_id', $userId)->first();
    }

    private function attendanceErrorMessage(DomainException $exception): string
    {
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, 'outside_area') => 'Bạn đang ở ngoài phạm vi chấm công cho phép.',
            str_contains($message, 'poor_accuracy'), str_contains($message, 'accuracy') => 'Độ chính xác vị trí hiện tại chưa đạt yêu cầu. Vui lòng bật định vị chính xác và thử lại.',
            str_contains($message, 'no_eligible_location'), str_contains($message, 'location') => 'Không tìm thấy địa điểm chấm công phù hợp với vị trí hiện tại.',
            str_contains($message, 'already_checked_in') => 'Bạn đã chấm công vào cho ca làm việc này.',
            str_contains($message, 'already_checked_out') => 'Bạn đã chấm công ra cho ca làm việc này.',
            str_contains($message, 'not_checked_in'), str_contains($message, 'missing_check_in') => 'Bạn chưa chấm công vào nên chưa thể chấm công ra.',
            default => 'Không thể hoàn tất thao tác chấm công. Vui lòng kiểm tra lại và thử lần nữa.',
        };
    }
}

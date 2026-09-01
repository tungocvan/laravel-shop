<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Services\AttendanceDemoDataService;
use RuntimeException;

class AttendanceDemoOperationsController extends Controller
{
    public function index(): View
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return view('Attendance::admin.demo-operations', [
            'demoRecords' => AttendanceRecord::query()->where('session_key', 'like', 'demo-%')->count(),
            'demoAdjustments' => AttendanceAdjustmentRequest::query()->where('reason', 'like', '[DEMO]%')->count(),
        ]);
    }

    public function seed(AttendanceDemoDataService $service): RedirectResponse
    {
        try {
            $service->seed();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', 'Đã tạo hoặc cập nhật dữ liệu demo Attendance.');
    }

    public function reset(AttendanceDemoDataService $service): RedirectResponse
    {
        try {
            $deleted = $service->reset();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', "Đã xóa {$deleted} bản ghi Attendance demo. User và EmployeeProfile được giữ nguyên.");
    }
}

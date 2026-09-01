<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceShift;
use Modules\Attendance\Services\AttendanceAdminConfigService;

class AttendanceShiftsController extends Controller
{
    public function index(): View
    {
        return view('Attendance::admin.shifts', [
            'shifts' => AttendanceShift::query()->orderByDesc('is_default')->orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request, AttendanceAdminConfigService $service): RedirectResponse
    {
        $attributes = $this->validated($request);

        try {
            $service->saveShift(new AttendanceShift, $attributes);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['shift' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', 'Đã tạo ca làm việc.');
    }

    public function update(Request $request, AttendanceShift $shift, AttendanceAdminConfigService $service): RedirectResponse
    {
        $attributes = $this->validated($request, $shift);

        try {
            $service->saveShift($shift, $attributes);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['shift' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', 'Đã cập nhật ca làm việc.');
    }

    private function validated(Request $request, ?AttendanceShift $shift = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', Rule::unique('attendance_shifts', 'code')->ignore($shift?->id)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'early_leave_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}

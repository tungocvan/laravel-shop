<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Services\AttendanceAdminConfigService;

class AttendanceLocationsController extends Controller
{
    public function index(): View
    {
        return view('Attendance::admin.locations', [
            'locations' => AttendanceLocation::query()->orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request, AttendanceAdminConfigService $service): RedirectResponse
    {
        $attributes = $this->validated($request);

        try {
            $service->saveLocation(new AttendanceLocation, $attributes);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['location' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', 'Đã tạo địa điểm chấm công.');
    }

    public function update(Request $request, AttendanceLocation $location, AttendanceAdminConfigService $service): RedirectResponse
    {
        $attributes = $this->validated($request, $location);

        try {
            $service->saveLocation($location, $attributes);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['location' => $exception->getMessage()]);
        }

        return back()->with('attendance_success', 'Đã cập nhật địa điểm chấm công.');
    }

    private function validated(Request $request, ?AttendanceLocation $location = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', Rule::unique('attendance_locations', 'code')->ignore($location?->id)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:1,10000'],
            'maximum_accuracy_meters' => ['required', 'integer', 'between:1,1000'],
        ]);

        foreach (['is_active', 'check_in_enabled', 'check_out_enabled'] as $boolean) {
            $validated[$boolean] = $request->boolean($boolean);
        }

        return $validated;
    }
}

<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Services\AttendanceAdminConfigService;
use Modules\Attendance\Services\AttendanceGeocodingService;

class AttendanceLocationsController extends Controller
{
    public function index(): View
    {
        return view('Attendance::admin.locations', [
            'locations' => AttendanceLocation::query()->orderBy('name')->paginate(10),
        ]);
    }

    public function geocode(Request $request, AttendanceGeocodingService $service): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
        ]);

        try {
            return response()->json($service->geocode($validated['address']));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
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
            'address' => ['nullable', 'string', 'max:500'],
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

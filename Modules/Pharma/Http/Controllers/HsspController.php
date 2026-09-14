<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineProfile;

class HsspController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $perPage = in_array($request->integer('per_page', 25), [10, 25, 50, 100], true)
            ? $request->integer('per_page', 25)
            : 25;

        $profiles = MedicineProfile::query()
            ->with('medicine')
            ->when($search !== '', fn ($query) => $query->whereHas('medicine', fn ($medicine) => $medicine
                ->where('name', 'like', "%{$search}%")
                ->orWhere('medicine_code', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('profile_status', $status))
            ->where('is_current', true)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('Pharma::pages.hssp.index', [
            'profiles' => $profiles,
            'search' => $search,
            'status' => $status,
            'perPage' => $perPage,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(Medicine $medicine): View
    {
        return view('Pharma::pages.hssp.form', [
            'medicine' => $medicine,
            'profile' => new MedicineProfile([
                'profile_version' => (string) (($medicine->profiles()->count()) + 1),
                'profile_status' => MedicineProfile::STATUS_NEEDS_REVIEW,
                'is_current' => true,
            ]),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request, Medicine $medicine): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($medicine, $data): void {
            if ($data['is_current']) {
                $medicine->profiles()->update(['is_current' => false]);
            }

            $medicine->profiles()->create($data + [
                'created_by' => auth('admin')->id(),
                'updated_by' => auth('admin')->id(),
            ]);
        });

        return redirect()->route('admin.pharma.hssp.index')
            ->with('success', 'Đã tạo HSSP cho thuốc '.$medicine->name.'.');
    }

    public function edit(Medicine $medicine, MedicineProfile $profile): View
    {
        abort_unless($profile->medicine_id === $medicine->id, 404);

        return view('Pharma::pages.hssp.form', [
            'medicine' => $medicine,
            'profile' => $profile,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Medicine $medicine, MedicineProfile $profile): RedirectResponse
    {
        abort_unless($profile->medicine_id === $medicine->id, 404);
        $data = $this->validated($request, $profile);

        DB::transaction(function () use ($medicine, $profile, $data): void {
            if ($data['is_current']) {
                $medicine->profiles()->whereKeyNot($profile->id)->update(['is_current' => false]);
            }

            $profile->update($data + ['updated_by' => auth('admin')->id()]);
        });

        return redirect()->route('admin.pharma.hssp.index')
            ->with('success', 'Đã cập nhật HSSP của thuốc '.$medicine->name.'.');
    }

    private function validated(Request $request, ?MedicineProfile $profile = null): array
    {
        $data = $request->validate([
            'profile_version' => ['required', 'string', 'max:50'],
            'profile_status' => ['required', 'in:needs_review,verified,expired,incomplete'],
            'profile_link' => ['nullable', 'url', 'max:2000'],
            'source' => ['nullable', 'string', 'max:100'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'verified_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $duplicate = MedicineProfile::query()
            ->where('medicine_id', $profile?->medicine_id ?? (int) $request->route('medicine')->id)
            ->where('profile_version', $data['profile_version'])
            ->when($profile, fn ($query) => $query->whereKeyNot($profile->id))
            ->exists();

        if ($duplicate) {
            abort(422, 'Phiên bản HSSP này đã tồn tại cho thuốc.');
        }

        $data['is_current'] = $request->boolean('is_current', true);

        return $data;
    }

    private function statusOptions(): array
    {
        return [
            MedicineProfile::STATUS_NEEDS_REVIEW => 'Cần rà soát',
            MedicineProfile::STATUS_VERIFIED => 'Đã xác minh',
            MedicineProfile::STATUS_INCOMPLETE => 'Chưa đầy đủ',
            MedicineProfile::STATUS_EXPIRED => 'Hết hiệu lực',
        ];
    }
}

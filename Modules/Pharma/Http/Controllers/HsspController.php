<?php

namespace Modules\Pharma\Http\Controllers;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Services\DossierManager;
use App\Dossiers\Services\DossierStorageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineProfile;
use Modules\Pharma\Services\HsspDossierTemplateService;

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

    public function create(Medicine $medicine, HsspDossierTemplateService $templates, DossierStorageService $storage): View
    {
        return view('Pharma::pages.hssp.form', [
            'medicine' => $medicine,
            'profile' => new MedicineProfile([
                'profile_version' => (string) ($medicine->profiles()->count() + 1),
                'profile_status' => MedicineProfile::STATUS_NEEDS_REVIEW,
                'is_current' => true,
            ]),
            'statusOptions' => $this->statusOptions(),
            'dossierTemplate' => $templates->get(),
            'dossier' => null,
            'googleDriveConnected' => $storage->googleDriveConnected(),
            'uploadLimits' => $storage->uploadLimits(),
        ]);
    }

    public function store(
        Request $request,
        Medicine $medicine,
        HsspDossierTemplateService $templates,
        DossierManager $dossiers,
        DossierStorageService $storage
    ): RedirectResponse {
        $data = $this->validated($request, $medicine);
        $request->validate([
            'items.gmp.effective_to' => ['required', 'date'],
            'items.registration.effective_to' => ['required', 'date'],
            'item_files.*.*' => ['nullable', 'file', 'max:20480'],
            'master_files.*' => ['nullable', 'file', 'max:51200'],
            'custom_items.*.title' => ['nullable', 'string', 'max:255'],
            'custom_items.*.effective_to' => ['nullable', 'date'],
            'custom_files.*.*' => ['nullable', 'file', 'max:20480'],
            'storage_targets' => ['required', 'array', 'min:1'],
            'storage_targets.*' => ['in:local,google_drive'],
        ], [
            'items.gmp.effective_to.required' => 'Hiệu lực GMP là bắt buộc.',
            'items.registration.effective_to.required' => 'Hiệu lực số đăng ký là bắt buộc.',
        ]);

        $targets = array_values((array) $request->input('storage_targets', []));
        $template = $templates->get();
        $profile = DB::transaction(function () use ($medicine, $data, $request): MedicineProfile {
            if ($data['is_current']) {
                $medicine->profiles()->update(['is_current' => false]);
            }

            return $medicine->profiles()->create($data + [
                'effective_from' => $request->input('items.registration.effective_from'),
                'effective_to' => $request->input('items.registration.effective_to'),
                'created_by' => auth('admin')->id(),
                'updated_by' => auth('admin')->id(),
            ]);
        });

        $itemMetadata = $request->input('items', []);
        $itemMetadata['registration']['document_number'] = $itemMetadata['registration']['document_number']
            ?? $medicine->registration_number;

        $dossier = $dossiers->createFromTemplate($template, $profile, [
            'version' => $profile->profile_version,
            'status' => $profile->profile_status,
            'is_current' => $profile->is_current,
            'metadata' => ['medicine_id' => $medicine->id, 'medicine_code' => $medicine->medicine_code],
        ], $itemMetadata);

        $root = 'Pharma/HSSP/'.($medicine->medicine_code ?: 'medicine-'.$medicine->id).'/profile-'.$profile->id;
        foreach ($dossier->items as $item) {
            foreach ($request->file('item_files.'.$item->code, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets);
            }
        }

        foreach ((array) $request->input('custom_items', []) as $index => $custom) {
            $title = trim((string) ($custom['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $item = $dossier->items()->create([
                'code' => 'custom-'.($index + 1),
                'title' => $title,
                'sort_order' => 100 + (int) $index,
                'metadata' => ['effective_to' => $custom['effective_to'] ?? null],
            ]);
            foreach ($request->file('custom_files.'.$index, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets);
            }
        }

        foreach ($request->file('master_files', []) as $file) {
            $storage->store($dossier, null, $file, $root, 'master', $targets);
        }

        return redirect()->route('admin.pharma.hssp.index')
            ->with('success', 'Đã tạo bộ HSSP cho thuốc '.$medicine->name.'.');
    }

    public function edit(Medicine $medicine, MedicineProfile $profile, HsspDossierTemplateService $templates, DossierStorageService $storage): View
    {
        abort_unless($profile->medicine_id === $medicine->id, 404);

        return view('Pharma::pages.hssp.form', [
            'medicine' => $medicine,
            'profile' => $profile,
            'statusOptions' => $this->statusOptions(),
            'dossierTemplate' => $templates->get(),
            'dossier' => Dossier::query()->with(['items.attachments', 'attachments'])->where('owner_type', MedicineProfile::class)->where('owner_id', $profile->id)->latest('id')->first(),
            'googleDriveConnected' => $storage->googleDriveConnected(),
            'uploadLimits' => $storage->uploadLimits(),
        ]);
    }

    public function update(Request $request, Medicine $medicine, MedicineProfile $profile, HsspDossierTemplateService $templates, DossierManager $dossiers, DossierStorageService $storage): RedirectResponse
    {
        abort_unless($profile->medicine_id === $medicine->id, 404);
        $data = $this->validated($request, $medicine, $profile);
        $request->validate([
            'items.gmp.effective_to' => ['required', 'date'],
            'items.registration.effective_to' => ['required', 'date'],
            'item_files.*.*' => ['nullable', 'file', 'max:20480'],
            'master_files.*' => ['nullable', 'file', 'max:51200'],
            'existing_custom_items.*.title' => ['nullable', 'string', 'max:255'],
            'existing_custom_items.*.effective_to' => ['nullable', 'date'],
            'existing_custom_files.*.*' => ['nullable', 'file', 'max:20480'],
            'custom_items.*.title' => ['nullable', 'string', 'max:255'],
            'custom_items.*.effective_to' => ['nullable', 'date'],
            'custom_files.*.*' => ['nullable', 'file', 'max:20480'],
            'storage_targets' => ['required', 'array', 'min:1'],
            'storage_targets.*' => ['in:local,google_drive'],
        ]);
        $targets = array_values((array) $request->input('storage_targets', []));

        DB::transaction(function () use ($medicine, $profile, $data, $request): void {
            if ($data['is_current']) {
                $medicine->profiles()->where('id', '!=', $profile->id)->update(['is_current' => false]);
            }

            $profile->update($data + [
                'effective_from' => $request->input('items.registration.effective_from'),
                'effective_to' => $request->input('items.registration.effective_to'),
                'updated_by' => auth('admin')->id(),
            ]);
        });

        $dossier = Dossier::query()
            ->with('items')
            ->where('owner_type', MedicineProfile::class)
            ->where('owner_id', $profile->id)
            ->latest('id')
            ->first();

        if (! $dossier) {
            $dossier = $dossiers->createFromTemplate($templates->get(), $profile, [
                'version' => $profile->profile_version,
                'status' => $profile->profile_status,
                'is_current' => $profile->is_current,
                'metadata' => ['medicine_id' => $medicine->id, 'medicine_code' => $medicine->medicine_code],
            ], $request->input('items', []));
        } else {
            foreach ($dossier->items as $item) {
                if ($request->has('items.'.$item->code)) {
                    $item->update(['metadata' => $request->input('items.'.$item->code, [])]);
                }
            }
            $dossier->update([
                'version' => $profile->profile_version,
                'status' => $profile->profile_status,
                'is_current' => $profile->is_current,
                'updated_by' => auth('admin')->id(),
            ]);
        }

        $root = 'Pharma/HSSP/'.($medicine->medicine_code ?: 'medicine-'.$medicine->id).'/profile-'.$profile->id;
        foreach ($dossier->items as $item) {
            foreach ($request->file('item_files.'.$item->code, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets);
            }
        }
        foreach ((array) $request->input('existing_custom_items', []) as $itemId => $custom) {
            $item = $dossier->items()->whereNull('template_item_id')->find($itemId);
            if (! $item) {
                continue;
            }
            $item->update([
                'title' => trim((string) ($custom['title'] ?? $item->title)) ?: $item->title,
                'metadata' => ['effective_to' => $custom['effective_to'] ?? null],
            ]);
            foreach ($request->file('existing_custom_files.'.$itemId, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets);
            }
        }

        foreach ((array) $request->input('custom_items', []) as $index => $custom) {
            $title = trim((string) ($custom['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $item = $dossier->items()->create([
                'code' => 'custom-'.now()->format('YmdHis').'-'.($index + 1),
                'title' => $title,
                'sort_order' => 100 + $dossier->items()->whereNull('template_item_id')->count(),
                'metadata' => ['effective_to' => $custom['effective_to'] ?? null],
            ]);
            foreach ($request->file('custom_files.'.$index, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets);
            }
        }

        foreach ($request->file('master_files', []) as $file) {
            $storage->store($dossier, null, $file, $root, 'master', $targets);
        }

        return redirect()->route('admin.pharma.hssp.index')
            ->with('success', 'Đã cập nhật HSSP của thuốc '.$medicine->name.'.');
    }

    private function validated(Request $request, Medicine $medicine, ?MedicineProfile $profile = null): array
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
            ->where('medicine_id', $medicine->id)
            ->where('profile_version', $data['profile_version'])
            ->when($profile, fn ($query) => $query->where('id', '!=', $profile->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'profile_version' => 'Phiên bản HSSP này đã tồn tại cho thuốc.',
            ]);
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

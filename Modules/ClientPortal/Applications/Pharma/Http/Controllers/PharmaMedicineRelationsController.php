<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Services\DossierManager;
use App\Dossiers\Services\DossierStorageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineProfile;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Services\SupplierTrackingService;
use Modules\Pharma\Services\HsspDossierTemplateService;

final class PharmaMedicineRelationsController extends Controller
{
    private function authorizeAction(Request $request, ApplicationRegistry $registry, string $action): void
    {
        abort_unless($request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.products.'.$action), 403);
    }

    private function viewData(ApplicationRegistry $registry, ClientPortalSettingsService $settings, Medicine $medicine): array
    {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'medicine' => $medicine,
        ];
    }

    public function hssp(int $medicine, Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $this->authorizeAction($request, $registry, 'hssp.view');
        $master = Medicine::query()->with(['currentProfile'])->findOrFail($medicine);

        $profile = $master->currentProfile;
        $dossier = $profile ? Dossier::query()->with(['items.attachments', 'attachments'])
            ->where('owner_type', MedicineProfile::class)->where('owner_id', $profile->id)->latest('id')->first() : null;

        return view('ClientPortal::applications.pharma.medicine-hssp', $this->viewData($registry, $settings, $master) + [
            'canCreateHssp' => $registry->userCan($request->user('web'), 'client.pharma.products.hssp.create'),
            'canUpdateHssp' => $registry->userCan($request->user('web'), 'client.pharma.products.hssp.update'),
            'dossier' => $dossier,
            'dossierTemplate' => app(HsspDossierTemplateService::class)->get(),
            'googleDriveConnected' => app(DossierStorageService::class)->googleDriveConnected(),
            'uploadLimits' => app(DossierStorageService::class)->uploadLimits(),
        ]);
    }

    public function createHssp(int $medicine, Request $request, ApplicationRegistry $registry, HsspDossierTemplateService $templates, DossierManager $dossiers): RedirectResponse
    {
        $this->authorizeAction($request, $registry, 'hssp.create');
        $data = $request->validate([
            'gmp_effective_to' => ['required', 'date'],
            'registration_effective_to' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $master = Medicine::query()->findOrFail($medicine);

        DB::transaction(function () use ($master, $data, $request, $templates, $dossiers): void {
            $master = Medicine::query()->lockForUpdate()->findOrFail($master->id);
            // A single current dossier is allowed. Do not overwrite existing HSSP.
            abort_if($master->profiles()->where('is_current', true)->exists(), 409, 'Thuốc đã có HSSP hiện hành.');
            $profile = $master->profiles()->create([
                'profile_version' => (string) ($master->profiles()->count() + 1),
                'profile_status' => MedicineProfile::STATUS_INCOMPLETE,
                'is_current' => true,
                'notes' => $data['notes'] ?? null,
                'effective_to' => $data['registration_effective_to'],
                'created_by' => $request->user('web')->id,
                'updated_by' => $request->user('web')->id,
            ]);
            $dossiers->createFromTemplate($templates->get(), $profile, [
                'version' => $profile->profile_version,
                'status' => $profile->profile_status,
                'is_current' => true,
                'metadata' => ['medicine_id' => $master->id, 'medicine_code' => $master->medicine_code],
            ], [
                'gmp' => ['effective_to' => $data['gmp_effective_to']],
                'registration' => [
                    'document_number' => $master->registration_number,
                    'effective_to' => $data['registration_effective_to'],
                ],
            ]);
        });

        return redirect()->route('client.pharma.products.hssp', $master->id)
            ->with('success', 'Đã tạo HSSP nháp (chưa đầy đủ). Cần bổ sung tài liệu và xác minh trước khi sử dụng.');
    }

    public function updateHssp(
        int $medicine,
        Request $request,
        ApplicationRegistry $registry,
        HsspDossierTemplateService $templates,
        DossierManager $dossiers,
        DossierStorageService $storage,
    ): RedirectResponse {
        $this->authorizeAction($request, $registry, 'hssp.update');
        $master = Medicine::query()->findOrFail($medicine);
        $profile = $master->currentProfile;
        abort_unless($profile, 404);

        $data = $request->validate([
            'profile_version' => ['required', 'string', 'max:50'],
            'profile_status' => ['required', 'in:needs_review,incomplete,expired'],
            'source' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_current' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.effective_from' => ['nullable', 'date'],
            'items.*.effective_to' => ['nullable', 'date'],
            'items.*.document_number' => ['nullable', 'string', 'max:255'],
            'item_files.*.*' => ['nullable', 'file', 'max:20480'],
            'master_files.*' => ['nullable', 'file', 'max:51200'],
            'storage_targets' => ['required', 'array', 'min:1'],
            'storage_targets.*' => ['in:local,google_drive'],
        ]);
        if (MedicineProfile::query()->where('medicine_id', $master->id)
            ->where('profile_version', $data['profile_version'])->whereKeyNot($profile->id)->exists()) {
            throw ValidationException::withMessages(['profile_version' => 'Phiên bản hồ sơ đã tồn tại.']);
        }
        if (in_array('google_drive', $data['storage_targets'], true) && ! $storage->googleDriveConnected()) {
            throw ValidationException::withMessages(['storage_targets' => 'Google Drive chưa kết nối.']);
        }
        $targets = array_values(array_unique($data['storage_targets']));
        $itemsData = $data['items'] ?? [];
        $dossier = DB::transaction(function () use ($master, $profile, $data, $itemsData, $templates, $dossiers, $request): Dossier {
            $locked = MedicineProfile::query()->lockForUpdate()->findOrFail($profile->id);
            abort_unless((int) $locked->medicine_id === (int) $master->id, 404);
            // The Client Portal editor operates on the current profile only.
            // Keep it current so it remains accessible after save.
            $current = true;
            if ($current) {
                $master->profiles()->whereKeyNot($locked->id)->update(['is_current' => false]);
            }
            $locked->update([
                'profile_version' => $data['profile_version'],
                'profile_status' => $data['profile_status'],
                'source' => $data['source'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_current' => $current,
                'effective_from' => $itemsData['registration']['effective_from'] ?? null,
                'effective_to' => $itemsData['registration']['effective_to'] ?? null,
                'updated_by' => $request->user('web')->id,
            ]);
            $existing = Dossier::query()->with('items')->where('owner_type', MedicineProfile::class)
                ->where('owner_id', $locked->id)->latest('id')->first();
            if (! $existing) {
                return $dossiers->createFromTemplate($templates->get(), $locked, [
                    'version' => $locked->profile_version,
                    'status' => $locked->profile_status,
                    'is_current' => $locked->is_current,
                    'metadata' => ['medicine_id' => $master->id, 'medicine_code' => $master->medicine_code],
                ], $itemsData);
            }
            foreach ($existing->items as $item) {
                if (isset($itemsData[$item->code]) && is_array($itemsData[$item->code])) {
                    $item->update(['metadata' => $itemsData[$item->code]]);
                }
            }
            $existing->update([
                'version' => $locked->profile_version,
                'status' => $locked->profile_status,
                'is_current' => $locked->is_current,
                'updated_by' => $request->user('web')->id,
            ]);
            return $existing;
        });

        $root = 'Pharma/HSSP/'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', $master->medicine_code ?: 'medicine-'.$master->id).'-'.$master->id;
        foreach ($dossier->items()->get() as $item) {
            foreach ($request->file('item_files.'.$item->code, []) as $file) {
                $storage->store($dossier, $item, $file, $root, 'item', $targets, 'pharma');
            }
        }
        foreach ($request->file('master_files', []) as $file) {
            $storage->store($dossier, null, $file, $root, 'master', $targets, 'pharma');
        }

        return redirect()->route('client.pharma.products.hssp', $master->id)
            ->with('success', 'Đã cập nhật HSSP và tiếp nhận tài liệu. Tài liệu Google Drive có thể đang chờ đồng bộ.');
    }

    public function supplierConditions(int $medicine, Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $this->authorizeAction($request, $registry, 'supplier-pricing');
        $master = Medicine::query()->findOrFail($medicine);
        $trackings = $master->supplierTrackings()->latest('id')->paginate(15);
        $editing = $request->integer('tracking') ? $master->supplierTrackings()->findOrFail($request->integer('tracking')) : null;
        $suppliers = Partner::query()->withPartnerType('supplier')->orderBy('name')->get(['id', 'name', 'tax_code']);
        $supplierService = app(SupplierTrackingService::class);
        $selectedFacilityIds = $editing?->facilities()->pluck('pharma_official_source_facilities.id')->all() ?? [];
        $facilitySearch = trim((string) $request->query('facility_search', ''));
        $facilities = $supplierService->facilityCandidates($facilitySearch, $selectedFacilityIds, 25);

        return view('ClientPortal::applications.pharma.medicine-supplier-conditions', $this->viewData($registry, $settings, $master) + [
            'trackings' => $trackings,
            'editing' => $editing,
            'suppliers' => $suppliers,
            'distributionRegions' => $supplierService->distributionRegions(),
            'distributionProvincesByRegion' => $supplierService->distributionProvincesByRegion(),
            'facilities' => $facilities,
            'selectedFacilityIds' => $selectedFacilityIds,
            'canManageConditions' => $registry->userCan($request->user('web'), 'client.pharma.products.supplier-conditions.manage'),
        ]);
    }
    public function saveSupplierConditions(int $medicine, Request $request, ApplicationRegistry $registry): RedirectResponse
    {
        $this->authorizeAction($request, $registry, 'supplier-conditions.manage');
        $master = Medicine::query()->findOrFail($medicine);
        $data = $request->validate([
            'tracking_id' => ['nullable', 'integer'],
            'partner_id' => ['required', 'integer', 'exists:partners,id'],
            'working_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:active,inactive,pending'],
            'import_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'invoice_price' => ['nullable', 'numeric', 'min:0'],
            'committed_quantity' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'string', 'max:255'],
            'distribution_scope' => ['required', 'in:all,regions,facilities'],
            'distribution_regions' => ['nullable', 'array'],
            'distribution_regions.*' => ['string', 'max:50'],
            'distribution_provinces' => ['nullable', 'array'],
            'distribution_provinces.*' => ['string', 'max:20'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', 'exists:pharma_official_source_facilities,id'],
            'supplier_representative' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'contract' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ], [
            'partner_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'partner_id.exists' => 'Nhà cung cấp không tồn tại.',
            'import_price.required' => 'Vui lòng nhập Giá nhập NCC ở tab Điều kiện thương mại.',
            'import_price.numeric' => 'Giá nhập NCC phải là số.',
            'import_price.min' => 'Giá nhập NCC không được âm.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            'distribution_scope.required' => 'Vui lòng chọn phạm vi phân phối.',
        ]);
        $partner = Partner::query()->findOrFail($data['partner_id']);
        if (! $partner->hasPartnerType('supplier')) {
            throw ValidationException::withMessages(['partner_id' => 'Đối tác được chọn không phải Nhà cung cấp.']);
        }
        $tracking = isset($data['tracking_id'])
            ? $master->supplierTrackings()->findOrFail($data['tracking_id'])
            : new SupplierTracking(['medicine_id' => $master->id]);
        $scope = $data['distribution_scope'];
        $service = app(SupplierTrackingService::class);
        $regionMap = $service->distributionRegions();
        $selectedRegions = array_values(array_intersect(array_keys($regionMap), $data['distribution_regions'] ?? []));
        $provinceMap = $service->distributionProvincesByRegion();
        $allowedProvinces = collect($provinceMap)->only($selectedRegions)->flatMap(fn ($items) => array_keys($items))->map(fn ($code) => (string) $code)->all();
        $selectedProvinces = array_values(array_intersect($allowedProvinces, $data['distribution_provinces'] ?? []));
        $facilityIds = $scope === 'facilities'
            ? OfficialSourceFacility::query()->where('is_active', true)->whereIn('id', $data['facility_ids'] ?? [])->pluck('id')->all()
            : [];
        $values = collect($data)->except(['tracking_id', 'contract', 'distribution_regions', 'distribution_provinces', 'facility_ids'])->all();
        $values['distribution_regions'] = $scope === 'regions' ? $selectedRegions : null;
        $values['distribution_provinces'] = $scope === 'regions' ? $selectedProvinces : null;
        $values['supplier_name'] = $partner->name;
        $values['supplier_name_normalized'] = mb_strtolower(trim($partner->name));
        if ($request->hasFile('contract')) {
            $values['contract_file_path'] = $request->file('contract')->store('Pharma/SupplierTrackings/'.$master->id, 'local');
        }
        $tracking->fill($values);
        $tracking->medicine_id = $master->id;
        $tracking->save();
        $tracking->facilities()->sync($facilityIds);

        return redirect()->route('client.pharma.products.supplier-conditions', $master->id)
            ->with('success', 'Đã lưu điều kiện thương mại cho thuốc.');
    }

}

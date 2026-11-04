<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Dossiers\Services\DossierManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineProfile;
use Modules\Pharma\Models\SupplierTracking;
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

        return view('ClientPortal::applications.pharma.medicine-hssp', $this->viewData($registry, $settings, $master) + [
            'canCreateHssp' => $registry->userCan($request->user('web'), 'client.pharma.products.hssp.create'),
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

    public function supplierConditions(int $medicine, Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $this->authorizeAction($request, $registry, 'supplier-pricing');
        $master = Medicine::query()->findOrFail($medicine);
        $trackings = $master->supplierTrackings()->latest('id')->paginate(15);

        return view('ClientPortal::applications.pharma.medicine-supplier-conditions', $this->viewData($registry, $settings, $master) + [
            'trackings' => $trackings,
        ]);
    }
}

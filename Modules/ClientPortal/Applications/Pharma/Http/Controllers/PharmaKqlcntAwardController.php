<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntWorkflowReadiness;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntMappingDiagnostics;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntLegacyWorkflowBridge;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntAllocationBlockers;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntMappingConfirmation;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Muasamcong\Services\KqlcntAwardReadService;

final class PharmaKqlcntAwardController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, KqlcntAwardReadService $awards): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'notify_no' => ['nullable', 'string', 'max:255'],
            'investor_code' => ['nullable', 'string', 'max:255'],
            'medicine' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);
        $items = $awards->paginate($filters);
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-awards', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('pharma', collect($application['features'])->firstWhere('key', 'kqlcnt-awards') ?? abort(404)),
            'items' => $items,
            'options' => $awards->filterOptions(),
            'dashboard' => $awards->dashboard(),
            'filters' => $filters,
            'canImportAwards' => $request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.kqlcnt-awards.import'),
            'canExportAwards' => $request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.kqlcnt-awards.export'),
            'canAllocateAwards' => $request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.bid-awards.allocate'),
            'canPolicyAwards' => $request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.bid-awards.commercial-policy'),
        ]);
    }

    public function show(int $awardItem, ApplicationRegistry $registry, ClientPortalSettingsService $settings, KqlcntAwardReadService $awards): View
    {
        $item = $awards->find($awardItem);
        $medicines = $awards->medicines($awardItem);
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-award-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $this->featurePresentation($settings, $application),
            'item' => $item,
            'medicines' => $medicines,
        ]);
    }
    public function allocation(
        int $awardItem,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        KqlcntAwardReadService $awards,
        KqlcntWorkflowReadiness $readiness,
        KqlcntMappingDiagnostics $diagnostics,
    ): View|RedirectResponse {
        return $this->workflowPreview($awardItem, 'allocation', $registry, $settings, $awards, $readiness, $diagnostics, app(KqlcntLegacyWorkflowBridge::class));
    }

    public function confirmMapping(
        int $awardItem,
        ApplicationRegistry $registry,
        KqlcntMappingConfirmation $confirmation,
    ): RedirectResponse {
        $user = request()->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        $confirmation->confirm($awardItem, (int) $user->id);

        return redirect()->route('client.pharma.kqlcnt-awards.allocation', ['awardItem' => $awardItem])
            ->with('success', 'Đã xác nhận liên kết KQLCNT với Pharma.');
    }

    public function commercialPolicy(
        int $awardItem,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        KqlcntAwardReadService $awards,
        KqlcntWorkflowReadiness $readiness,
        KqlcntMappingDiagnostics $diagnostics,
    ): View|RedirectResponse {
        return $this->workflowPreview($awardItem, 'commercial-policy', $registry, $settings, $awards, $readiness, $diagnostics, app(KqlcntLegacyWorkflowBridge::class));
    }

    private function workflowPreview(
        int $awardItem,
        string $mode,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        KqlcntAwardReadService $awards,
        KqlcntWorkflowReadiness $readiness,
        KqlcntMappingDiagnostics $diagnostics,
        KqlcntLegacyWorkflowBridge $bridge,
    ): View|RedirectResponse {
        $permission = $mode === 'allocation'
            ? 'client.pharma.bid-awards.allocate'
            : 'client.pharma.bid-awards.commercial-policy';
        abort_unless(request()->user('web') && $registry->userCan(request()->user('web'), $permission), 403);

        $scopeKey = $bridge->scopeKey($awardItem);
        if ($scopeKey !== null) {
            return redirect()->route(
                $mode === 'allocation'
                    ? 'client.pharma.bid-awards.allocation'
                    : 'client.pharma.bid-awards.commercial-policy',
                ['scope' => $scopeKey]
            );
        }

        $item = $awards->find($awardItem);
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-workflow-preview', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $this->featurePresentation($settings, $application),
            'item' => $item,
            'mode' => $mode,
            'readiness' => $readiness->forAward($awardItem),
            'mappingDiagnostics' => $diagnostics->inspect($awardItem),
            'allocationBlockers' => app(KqlcntAllocationBlockers::class)->inspect($awardItem),
            'canConfirmMapping' => $mode === 'allocation' && app(KqlcntLegacyWorkflowBridge::class)->verifiedMappingCandidateIds($awardItem) !== [],
            'medicines' => $awards->medicines($awardItem),
        ]);
    }

    /**
     * Shared managed feature presentation for detail and workflow previews.
     * Keep the normalized feature lookup in one place.
     */
    private function featurePresentation(ClientPortalSettingsService $settings, array $application): array
    {
        $feature = collect($application['features'])->firstWhere('key', 'kqlcnt-awards') ?? abort(404);

        return $settings->featurePresentation('pharma', $feature);
    }

}

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
use Modules\Pharma\Services\KqlcntExcelAwardBootstrapService;
use Modules\Pharma\Services\ClientBidAwardWorkflow;
use Modules\Pharma\Services\KqlcntExcelAwardRemovalService;

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

    public function resetExcelSetup(int $awardItem, Request $request, ApplicationRegistry $registry, KqlcntExcelAwardRemovalService $removal): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.bid-awards.allocate')
            && $registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $request->validate(['acknowledge' => ['required', 'accepted']]);
        $removal->reset($awardItem, (int) $user->id);

        return redirect()->route('client.pharma.kqlcnt-awards.show', ['awardItem' => $awardItem])
            ->with('success', 'Đã reset phân bổ và chính sách về trạng thái chưa thiết lập.');
    }

    public function deleteExcel(int $awardItem, Request $request, ApplicationRegistry $registry, KqlcntExcelAwardRemovalService $removal): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.kqlcnt-awards.import')
            && $registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        $request->validate(['acknowledge' => ['required', 'accepted'], 'confirm_notice' => ['required', 'string']]);
        $notice = app(KqlcntAwardReadService::class)->find($awardItem)->notify_no;
        abort_unless(hash_equals((string) $notice, (string) $request->input('confirm_notice')), 422, 'Mã TBMT xác nhận không khớp.');
        $removal->delete($awardItem, (int) $user->id);

        return redirect()->route('client.pharma.kqlcnt-awards.index')
            ->with('success', 'Đã xóa vĩnh viễn hồ sơ import Excel.');
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
            'canResetExcel' => request()->user('web') && $registry->userCan(request()->user('web'), 'client.pharma.bid-awards.allocate') && $registry->userCan(request()->user('web'), 'client.pharma.bid-awards.commercial-policy') && app(KqlcntExcelAwardRemovalService::class)->hasSetup($awardItem),
            'canDeleteExcel' => request()->user('web') && $registry->userCan(request()->user('web'), 'client.pharma.kqlcnt-awards.import') && $registry->userCan(request()->user('web'), 'client.pharma.bid-awards.allocate'),
        ]);
    }
    /**
     * Canonical KQLCNT routes reuse the existing Bid Awards workflow and views.
     * The bridge must resolve a verified one-to-one scope before any mutation.
     */
    private function linkedScope(int $awardItem, KqlcntLegacyWorkflowBridge $bridge): string
    {
        $scope = $bridge->scopeKey($awardItem);
        abort_if($scope === null, 409, 'Hồ sơ KQLCNT chưa xác minh liên kết Pharma.');

        return $scope;
    }

    private function canonicalView(View $view, int $awardItem): View
    {
        return $view->with('kqlcntAwardItem', $awardItem);
    }

    public function allocation(
        int $awardItem, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): View|RedirectResponse {
        if ($bridge->scopeKey($awardItem) === null) {
            return $this->workflowPreview($awardItem, 'allocation', $registry, app(ClientPortalSettingsService::class), app(KqlcntAwardReadService::class), app(KqlcntWorkflowReadiness::class), app(KqlcntMappingDiagnostics::class), $bridge);
        }

        return $this->canonicalView(app(PharmaApplicationController::class)->bidAwardAllocation(
            $this->linkedScope($awardItem, $bridge), $request, $registry, $workflow
        ), $awardItem);
    }

    public function saveAllocationSetup(
        int $awardItem, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): RedirectResponse {
        app(PharmaApplicationController::class)->storeBidAwardDistributionSetup(
            $this->linkedScope($awardItem, $bridge), $request, $registry, $workflow
        );

        return redirect()->route('client.pharma.kqlcnt-awards.allocation', ['awardItem' => $awardItem])
            ->with('success', 'Đã lưu thiết lập phân bổ.');
    }

    public function hospitalAllocation(
        int $awardItem, int $partner, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): View {
        return $this->canonicalView(app(PharmaApplicationController::class)->bidAwardHospitalAllocation(
            $this->linkedScope($awardItem, $bridge), $partner, $request, $registry, $workflow
        ), $awardItem);
    }

    public function saveHospitalAllocation(
        int $awardItem, int $partner, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): RedirectResponse {
        app(PharmaApplicationController::class)->storeBidAwardHospitalAllocation(
            $this->linkedScope($awardItem, $bridge), $partner, $request, $registry, $workflow
        );

        return redirect()->route('client.pharma.kqlcnt-awards.allocation.hospital', [
            'awardItem' => $awardItem, 'partner' => $partner,
        ])->with('success', 'Đã lưu phân bổ số lượng.');
    }

    public function hospitalPolicy(
        int $awardItem, int $partner, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): View {
        return $this->canonicalView(app(PharmaApplicationController::class)->bidAwardHospitalCommercialPolicy(
            $this->linkedScope($awardItem, $bridge), $partner, $request, $registry, $workflow
        ), $awardItem);
    }

    public function saveHospitalPolicy(
        int $awardItem, int $partner, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): RedirectResponse {
        app(PharmaApplicationController::class)->storeBidAwardHospitalCommercialPolicy(
            $this->linkedScope($awardItem, $bridge), $partner, $request, $registry, $workflow
        );

        return redirect()->route('client.pharma.kqlcnt-awards.allocation.hospital.policy', [
            'awardItem' => $awardItem, 'partner' => $partner,
        ])->with('success', 'Đã lưu chính sách kinh doanh bệnh viện.');
    }

    public function confirmMapping(
        int $awardItem,
        ApplicationRegistry $registry,
        KqlcntMappingConfirmation $confirmation,
    ): RedirectResponse {
        $user = request()->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        request()->validate(['acknowledge' => ['required', 'accepted']]);
        $confirmation->confirm($awardItem, (int) $user->id);

        return redirect()->route('client.pharma.kqlcnt-awards.allocation', ['awardItem' => $awardItem])
            ->with('success', 'Đã xác nhận liên kết KQLCNT với Pharma.');
    }

    public function bootstrapExcel(
        int $awardItem,
        ApplicationRegistry $registry,
        KqlcntExcelAwardBootstrapService $bootstrap,
    ): RedirectResponse {
        $user = request()->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        request()->validate(['acknowledge' => ['required', 'accepted']]);
        $created = $bootstrap->confirm($awardItem, (int) $user->id);

        return redirect()->route('client.pharma.kqlcnt-awards.allocation', ['awardItem' => $awardItem])
            ->with('success', 'Đã khởi tạo '.$created.' sản phẩm Pharma từ hồ sơ Excel. Bạn có thể thiết lập phân bổ và chính sách kinh doanh.');
    }

    public function commercialPolicy(
        int $awardItem, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): View|RedirectResponse {
        if ($bridge->scopeKey($awardItem) === null) {
            return $this->workflowPreview($awardItem, 'commercial-policy', $registry, app(ClientPortalSettingsService::class), app(KqlcntAwardReadService::class), app(KqlcntWorkflowReadiness::class), app(KqlcntMappingDiagnostics::class), $bridge);
        }

        return $this->canonicalView(app(PharmaApplicationController::class)->bidAwardCommercialPolicy(
            $this->linkedScope($awardItem, $bridge), $request, $registry, $workflow
        ), $awardItem);
    }

    public function saveCommercialPolicy(
        int $awardItem, Request $request, ApplicationRegistry $registry,
        ClientBidAwardWorkflow $workflow, KqlcntLegacyWorkflowBridge $bridge,
    ): RedirectResponse {
        $result = app(PharmaApplicationController::class)->storeBidAwardCommercialPolicy(
            $this->linkedScope($awardItem, $bridge), $request, $registry, $workflow
        );

        if (! $workflow->commercialPolicyReady($workflow->contextAward($this->linkedScope($awardItem, $bridge)))) {
            return redirect()->route('client.pharma.kqlcnt-awards.commercial-policy', ['awardItem' => $awardItem])
                ->withErrors(['commercial_policy' => 'Hãy nhập chính sách (%) cho tất cả sản phẩm đang được phân bổ trước khi tiếp tục phân công User quản lý.']);
        }

        // Manager assignment remains a legacy downstream workflow in this batch.
        return $result;
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
            'canBootstrapExcel' => $mode === 'allocation' && app(KqlcntExcelAwardBootstrapService::class)->eligible($awardItem),
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

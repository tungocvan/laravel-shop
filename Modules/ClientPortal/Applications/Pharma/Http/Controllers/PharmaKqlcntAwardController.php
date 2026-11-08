<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntWorkflowReadiness;
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
            'presentation' => $settings->featurePresentation('pharma', collect($application['features'])->firstWhere('key', 'kqlcnt-awards') ?? abort(404)),
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
    ): View {
        return $this->workflowPreview($awardItem, 'allocation', $registry, $settings, $awards, $readiness);
    }

    public function commercialPolicy(
        int $awardItem,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        KqlcntAwardReadService $awards,
        KqlcntWorkflowReadiness $readiness,
    ): View {
        return $this->workflowPreview($awardItem, 'commercial-policy', $registry, $settings, $awards, $readiness);
    }

    private function workflowPreview(
        int $awardItem,
        string $mode,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        KqlcntAwardReadService $awards,
        KqlcntWorkflowReadiness $readiness,
    ): View {
        $item = $awards->find($awardItem);
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-workflow-preview', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('pharma', collect($application['features'])->firstWhere('key', 'kqlcnt-awards') ?? abort(404)),
            'item' => $item,
            'mode' => $mode,
            'readiness' => $readiness->forAward($awardItem),
            'medicines' => $awards->medicines($awardItem),
        ]);
    }

}

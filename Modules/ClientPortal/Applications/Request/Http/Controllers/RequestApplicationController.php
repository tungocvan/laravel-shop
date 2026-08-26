<?php

namespace Modules\ClientPortal\Applications\Request\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request as HttpRequest;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Queries\MyRequestsQuery;

final class RequestApplicationController extends Controller
{
    public function dashboard(
        HttpRequest $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MyRequestsQuery $myRequests,
        ApproverInboxQuery $approverInbox,
    ): View {
        $application = $registry->find('request');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $authorizedFeatures = collect($application['features'] ?? [])->filter(function (array $feature) use ($registry, $user): bool {
            $permission = $feature['permission'] ?? null;

            return $permission === null || $registry->userCan($user, $permission);
        })->values();

        $features = $settings->presentFeatures($application['key'], $authorizedFeatures);
        $applicationPresentation = $settings->applicationPresentation($application);
        $requestCounts = $myRequests->workspaceCounts((int) $user->getAuthIdentifier());
        $approvalCounts = $approverInbox->workloadSummary((int) $user->getAuthIdentifier());
        $processedCounts = $approverInbox->processedSummary((int) $user->getAuthIdentifier());

        return view('ClientPortal::applications.request.dashboard', compact(
            'application',
            'applicationPresentation',
            'features',
            'requestCounts',
            'approvalCounts',
            'processedCounts',
        ));
    }
}

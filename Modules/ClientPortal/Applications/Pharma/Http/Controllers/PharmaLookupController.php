<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class PharmaLookupController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.dav-prices.view'), 403);

        $feature = collect($application['features'] ?? [])->first(
            fn (array $item): bool => ($item['key'] ?? null) === 'lookups'
        );
        abort_if($feature === null, 404);

        $muasamcong = $registry->find('muasamcong');
        $canViewAwardPricing = $muasamcong !== null
            && $registry->userCan($user, $muasamcong['permission'] ?? 'client.muasamcong.access')
            && collect($muasamcong['features'] ?? [])->contains(fn (array $item): bool =>
                ($item['key'] ?? null) === 'drug-pricing'
                && $registry->userCan($user, $item['permission'] ?? 'client.muasamcong.drug-pricing.view'));

        return view('ClientPortal::applications.pharma.lookups', [
            'canViewAwardPricing' => $canViewAwardPricing,
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
        ]);
    }
}

<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\PublicAwardPriceLookup;

final class PharmaAwardPriceController extends Controller
{
    public function index(Request $request, PublicAwardPriceLookup $lookup, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.dav-prices.view'), 403);

        $validated = $request->validate(['keyword' => ['nullable', 'string', 'min:2', 'max:200']]);
        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $result = $keyword === '' ? null : $lookup->search($keyword);

        return view('ClientPortal::applications.pharma.award-prices', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'keyword' => $keyword,
            'result' => $result,
        ]);
    }
}

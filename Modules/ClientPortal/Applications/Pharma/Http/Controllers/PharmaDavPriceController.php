<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavPublicPriceLookup;
use RuntimeException;

final class PharmaDavPriceController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, DavPublicPriceLookup $lookup): View
    {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.dav-prices.view'), 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'between:1,1000'],
        ]);
        $keyword = trim((string) ($validated['q'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);
        $result = ['total' => 0, 'items' => []];
        $error = null;

        if ($keyword !== '') {
            try {
                $result = $lookup->search($keyword, $page);
            } catch (RuntimeException $exception) {
                report($exception);
                $error = 'Không thể tra cứu DAV lúc này. Vui lòng thử lại sau.';
            }
        }

        $feature = collect($application['features'] ?? [])->first(
            fn (array $item): bool => ($item['key'] ?? null) === 'dav-prices'
        );
        abort_if($feature === null, 404);

        return view('ClientPortal::applications.pharma.dav-prices', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'keyword' => $keyword,
            'page' => $page,
            'result' => $result,
            'error' => $error,
        ]);
    }
}

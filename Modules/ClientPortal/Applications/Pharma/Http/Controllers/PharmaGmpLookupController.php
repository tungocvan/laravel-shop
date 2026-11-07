<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavForeignGmpLookup;
use RuntimeException;

final class PharmaGmpLookupController extends Controller
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavForeignGmpLookup $lookup
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,1000'],
        ]);
        $keyword = trim((string) ($input['q'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        $result = ['total' => 0, 'items' => []];
        $error = null;

        // Empty query is intentional: browse all publicly listed GMP facilities.
        try {
            $result = $lookup->search($keyword, $page);
        } catch (RuntimeException $exception) {
            report($exception);
            $error = 'Không thể tải danh sách GMP lúc này. Vui lòng thử lại sau.';
        }

        return view('ClientPortal::applications.pharma.gmp-lookup', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'keyword' => $keyword,
            'page' => $page,
            'result' => $result,
            'error' => $error,
        ]);
    }

    public function show(
        Request $request,
        string $sourceId,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavForeignGmpLookup $lookup
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,1000'],
        ]);
        $keyword = trim((string) ($input['q'] ?? ''));
        $page = (int) ($input['page'] ?? 1);

        try {
            $item = $lookup->findOnPage($keyword, $page, $sourceId);
        } catch (RuntimeException $exception) {
            report($exception);
            abort(503, 'Không thể xác minh thông tin GMP từ nguồn công khai.');
        }
        abort_if($item === null, 404);

        return view('ClientPortal::applications.pharma.gmp-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'item' => $item,
            'keyword' => $keyword,
            'page' => $page,
        ]);
    }

    private function authorizedFeature(Request $request, ApplicationRegistry $registry): array
    {
        $application = $registry->find('pharma');
        abort_unless($application !== null && $request->user('web')
            && $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);

        $feature = collect($application['features'] ?? [])->first(
            fn (array $candidate): bool => ($candidate['key'] ?? null) === 'gmp'
        );
        abort_if($feature === null, 404);

        return [$application, $feature];
    }
}

<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavDrugRegistrationLookup;
use Modules\Pharma\Services\LookupWishlistService;
use RuntimeException;

abstract class BasePharmaRegistrationController extends Controller
{
    protected function indexFor(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavDrugRegistrationLookup $lookup,
        string $featureKey,
        string $routeBase
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry, $featureKey);
        $input = $this->validatedSearch($request);
        $field = $input['field'] ?? 'name';
        $keyword = trim((string) ($input['q'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        $result = ['total' => 0, 'items' => []];
        $error = null;
        // Avoid expensive full-dataset queries until the user searches.
        if ($keyword !== '') {
            try {
                $result = $lookup->search($field, $keyword, $page);
            } catch (RuntimeException $exception) {
                report($exception);
                $error = 'Không thể tra cứu giấy đăng ký thuốc từ nguồn công bố lúc này. Vui lòng thử lại sau.';
            }
        }

        $wishlistSource = $featureKey === 'drug-registrations' ? 'dav_registration' : 'traditional_registration';
        $savedIds = app(LookupWishlistService::class)->savedIds(
            (int) $request->user('web')->id,
            $wishlistSource,
            array_values(array_filter(array_column($result['items'], 'source_id')))
        );

        return view('ClientPortal::applications.pharma.drug-registrations', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'wishlistSource' => $wishlistSource,
            'savedIds' => $savedIds,
            'field' => $field,
            'keyword' => $keyword,
            'page' => $page,
            'routeBase' => $routeBase,
            'perPage' => $lookup->pageSize(),
            'result' => $result,
            'error' => $error,
        ]);
    }

    protected function showFor(
        Request $request,
        string $sourceId,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavDrugRegistrationLookup $lookup,
        string $featureKey,
        string $routeBase
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry, $featureKey);
        $input = $this->validatedSearch($request);
        $field = $input['field'] ?? 'name';
        $keyword = trim((string) ($input['q'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        abort_if($keyword === '', 404);
        try {
            $item = $lookup->findOnPage($field, $keyword, $page, $sourceId);
        } catch (RuntimeException $exception) {
            report($exception);
            abort(503, 'Không thể xác minh giấy đăng ký thuốc từ nguồn công bố.');
        }
        abort_if($item === null, 404);

        return view('ClientPortal::applications.pharma.drug-registration-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'item' => $item,
            'field' => $field,
            'keyword' => $keyword,
            'page' => $page,
            'routeBase' => $routeBase,
            'perPage' => $lookup->pageSize(),
        ]);
    }

    private function validatedSearch(Request $request): array
    {
        return $request->validate([
            'field' => ['nullable', Rule::in(array_keys(DavDrugRegistrationLookup::FIELDS))],
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'between:1,1000'],
        ]);
    }

    private function authorizedFeature(Request $request, ApplicationRegistry $registry, string $featureKey): array
    {
        $application = $registry->find('pharma');
        abort_unless($application !== null && $request->user('web')
            && $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);
        $feature = collect($application['features'] ?? [])->first(
            fn (array $candidate): bool => ($candidate['key'] ?? null) === $featureKey
        );
        abort_if($feature === null, 404);

        return [$application, $feature];
    }
}

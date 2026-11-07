<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavDrugRegistrationLookup;
use RuntimeException;

final class PharmaDrugRegistrationController extends Controller
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        DavDrugRegistrationLookup $lookup
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
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
                $error = 'Không thể tra cứu giấy đăng ký thuốc từ DAV lúc này. Vui lòng thử lại sau.';
            }
        }

        return view('ClientPortal::applications.pharma.drug-registrations', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'field' => $field,
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
        DavDrugRegistrationLookup $lookup
    ): View {
        [$application, $feature] = $this->authorizedFeature($request, $registry);
        $input = $this->validatedSearch($request);
        $field = $input['field'] ?? 'name';
        $keyword = trim((string) ($input['q'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        abort_if($keyword === '', 404);
        try {
            $item = $lookup->findOnPage($field, $keyword, $page, $sourceId);
        } catch (RuntimeException $exception) {
            report($exception);
            abort(503, 'Không thể xác minh giấy đăng ký thuốc từ DAV.');
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

    private function authorizedFeature(Request $request, ApplicationRegistry $registry): array
    {
        $application = $registry->find('pharma');
        abort_unless($application !== null && $request->user('web')
            && $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);
        $feature = collect($application['features'] ?? [])->first(
            fn (array $candidate): bool => ($candidate['key'] ?? null) === 'drug-registrations'
        );
        abort_if($feature === null, 404);

        return [$application, $feature];
    }
}

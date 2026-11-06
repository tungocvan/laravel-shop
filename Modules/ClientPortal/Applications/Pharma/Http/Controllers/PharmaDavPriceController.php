<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\DavPublicPriceLookup;
use Modules\Pharma\Services\DavMedicineCandidateMatcher;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Models\Medicine;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class PharmaDavPriceController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, DavPublicPriceLookup $lookup, DavMedicineCandidateMatcher $matcher): View
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

        $result['items'] = array_map(static fn (array $item): array => $item + ['match' => $matcher->inspect($item)], $result['items']);

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
    public function preview(Request $request, ApplicationRegistry $registry, DavPublicPriceLookup $lookup, DavMedicineCandidateMatcher $matcher): View
    {
        $this->authorizeImport($request, $registry);
        $input = $request->validate(['q' => ['required', 'string', 'max:120'], 'page' => ['required', 'integer', 'between:1,1000'], 'source_id' => ['required', 'string', 'max:60']]);
        $candidate = $this->sourceCandidate($lookup, $input);
        $match = $matcher->inspect($candidate);

        return view('ClientPortal::applications.pharma.dav-import-preview', compact('candidate', 'match', 'input'));
    }

    public function import(Request $request, ApplicationRegistry $registry, DavPublicPriceLookup $lookup, DavMedicineCandidateMatcher $matcher, MedicineService $medicines)
    {
        $this->authorizeImport($request, $registry);
        $input = $request->validate(['q' => ['required', 'string', 'max:120'], 'page' => ['required', 'integer', 'between:1,1000'], 'source_id' => ['required', 'string', 'max:60'], 'confirmed' => ['accepted']]);
        $candidate = $this->sourceCandidate($lookup, $input);
        // Serialize concurrent imports of the same DAV identity across requests.
        $lockKey = 'pharma:dav:import:'.hash('sha256', mb_strtolower(trim($candidate['name'])).'|'.trim($candidate['registration']));
        $medicine = Cache::lock($lockKey, 20)->block(5, function () use ($matcher, $candidate, $medicines) {
            $match = $matcher->inspect($candidate);
            if ($match['status'] !== 'new') {
                throw ValidationException::withMessages(['medicine' => 'Thuốc đã tồn tại hoặc cần đối chiếu thủ công. Không tạo mới tự động.']);
            }

            return $medicines->store([
                'name' => $candidate['name'],
                'product_type' => Medicine::PRODUCT_TYPE_MODERN,
                'registration_number' => $candidate['registration'],
                'active_ingredients' => mb_substr($candidate['ingredient'], 0, 255),
                'concentration' => mb_substr($candidate['strength'], 0, 255),
                'dosage_form' => mb_substr($candidate['dosage'], 0, 255),
                'unit' => mb_substr($candidate['unit'], 0, 255),
                'packaging_specification' => mb_substr($candidate['package'], 0, 255),
                'manufacturing_company' => mb_substr($candidate['manufacturer'], 0, 255),
                'manufacturing_country' => mb_substr($candidate['country'], 0, 255),
                'registered_company' => mb_substr($candidate['declarant'], 0, 255),
                'declared_price' => null,
                'selling_price' => null,
            ]);
        });

        return redirect()->route('client.pharma.products.edit', $medicine->id)->with('success', 'Đã tạo thuốc từ DAV. Vui lòng kiểm tra và hoàn thiện hồ sơ.');
    }

    private function authorizeImport(Request $request, ApplicationRegistry $registry): void
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.dav-prices.view')
            && $registry->userCan($user, 'client.pharma.products.create'), 403);
    }

    private function sourceCandidate(DavPublicPriceLookup $lookup, array $input): array
    {
        $items = $lookup->search($input['q'], (int) $input['page'])['items'];
        $candidate = collect($items)->first(fn (array $row): bool => $row['source_id'] !== '' && hash_equals($row['source_id'], $input['source_id']));
        if (! $candidate) {
            throw ValidationException::withMessages(['source_id' => 'Không tìm thấy thuốc DAV tương ứng. Vui lòng tra cứu lại.']);
        }

        return $candidate;
    }

}

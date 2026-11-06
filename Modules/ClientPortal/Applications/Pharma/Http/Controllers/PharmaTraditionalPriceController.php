<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MohTraditionalMedicineMatcher;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Services\MohTraditionalPriceLookup;
use RuntimeException;

final class PharmaTraditionalPriceController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, MohTraditionalPriceLookup $lookup, MohTraditionalMedicineMatcher $matcher): View
    {
        $this->authorizeView($request, $registry);
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'page' => ['nullable', 'integer', 'between:1,1000']]);
        $keyword = trim((string) ($validated['q'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);
        $result = ['total' => 0, 'items' => []];
        $error = null;
        if ($keyword !== '') {
            try {
                $result = $lookup->search($keyword, $page);
            } catch (RuntimeException $exception) {
                report($exception);
                $error = 'Không thể tra cứu giá thuốc đông dược lúc này. Vui lòng thử lại sau.';
            }
        }

        $result['items'] = array_map(fn (array $item): array => $item + [
            'match' => $matcher->inspect($item),
            'multi_package' => $this->hasConflictingSameUnitRows($result['items'], $item),
        ], $result['items']);

        $application = $registry->find('pharma');
        $feature = collect($application['features'] ?? [])->first(fn (array $item): bool => ($item['key'] ?? null) === 'traditional-prices');
        abort_if($feature === null, 404);

        return view('ClientPortal::applications.pharma.traditional-prices', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'keyword' => $keyword, 'page' => $page, 'result' => $result, 'error' => $error,
        ]);
    }

    public function preview(Request $request, ApplicationRegistry $registry, MohTraditionalPriceLookup $lookup, MohTraditionalMedicineMatcher $matcher): View
    {
        $this->authorizeImport($request, $registry);
        $input = $this->validatedSource($request);
        $items = $lookup->search($input['q'], (int) $input['page'])['items'];
        $candidate = $this->candidate($items, $input['source_id']);
        $match = $matcher->inspect($candidate);
        $multiPackage = $this->hasConflictingSameUnitRows($items, $candidate);

        return view('ClientPortal::applications.pharma.traditional-import-preview', compact('candidate', 'match', 'input', 'multiPackage'));
    }

    public function import(Request $request, ApplicationRegistry $registry, MohTraditionalPriceLookup $lookup, MohTraditionalMedicineMatcher $matcher, MedicineService $medicines): RedirectResponse
    {
        $this->authorizeImport($request, $registry);
        $input = $this->validatedSource($request) + $request->validate(['confirmed' => ['accepted']]);
        $items = $lookup->search($input['q'], (int) $input['page'])['items'];
        $candidate = $this->candidate($items, $input['source_id']);
        if ($this->hasConflictingSameUnitRows($items, $candidate)) {
            throw ValidationException::withMessages(['medicine' => 'Cùng tên, số đăng ký và ĐVT có nhiều quy cách/giá; cần đối chiếu thủ công trước khi đồng bộ.']);
        }

        $lockKey = 'pharma:moh-traditional:import:'.hash('sha256', mb_strtolower(trim($candidate['name'])).'|'.trim($candidate['registration']).'|'.mb_strtolower(trim($candidate['unit'])));
        $medicine = Cache::lock($lockKey, 20)->block(5, function () use ($matcher, $candidate, $medicines) {
            if ($matcher->inspect($candidate)['status'] !== 'new') {
                throw ValidationException::withMessages(['medicine' => 'Thuốc đã tồn tại hoặc cần đối chiếu thủ công.']);
            }

            return $medicines->storeWithDeclaredPriceOnly([
                'name' => $candidate['name'],
                'product_type' => Medicine::PRODUCT_TYPE_TRADITIONAL,
                'registration_number' => $candidate['registration'],
                'active_ingredients' => mb_substr($candidate['ingredient'], 0, 255),
                'concentration' => mb_substr($candidate['strength'], 0, 255),
                'dosage_form' => mb_substr($candidate['dosage'], 0, 255),
                'unit' => mb_substr($candidate['unit'], 0, 255),
                'packaging_specification' => mb_substr($candidate['package'], 0, 255),
                'manufacturing_company' => mb_substr($candidate['manufacturer'], 0, 255),
                'manufacturing_country' => mb_substr($candidate['country'], 0, 255),
                'registered_company' => mb_substr($candidate['declarant'], 0, 255),
                'declared_price' => $candidate['wholesale'],
            ]);
        });

        return redirect()->route('client.pharma.products.edit', $medicine->id)
            ->with('success', 'Đã tạo thuốc đông dược từ nguồn Bộ Y tế. Vui lòng hoàn thiện và kiểm tra quy cách.');
    }

    private function authorizeView(Request $request, ApplicationRegistry $registry): void
    {
        abort_unless($registry->find('pharma') && $request->user('web') &&
            $registry->userCan($request->user('web'), 'client.pharma.dav-prices.view'), 403);
    }

    private function authorizeImport(Request $request, ApplicationRegistry $registry): void
    {
        $this->authorizeView($request, $registry);
        abort_unless($registry->userCan($request->user('web'), 'client.pharma.products.create'), 403);
    }

    private function validatedSource(Request $request): array
    {
        return $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'page' => ['required', 'integer', 'between:1,1000'],
            'source_id' => ['required', 'string', 'max:60'],
        ]);
    }

    private function candidate(array $items, string $sourceId): array
    {
        $candidate = collect($items)->first(fn (array $item): bool =>
            $item['source_id'] !== '' && hash_equals($item['source_id'], $sourceId));
        if (! $candidate) {
            throw ValidationException::withMessages(['source_id' => 'Không tìm thấy bản ghi nguồn; vui lòng tra cứu lại.']);
        }

        return $candidate;
    }

    private function hasConflictingSameUnitRows(array $items, array $candidate): bool
    {
        $key = static fn (array $item): string => mb_strtolower(trim($item['name'])).'|'.mb_strtolower(trim($item['registration'])).'|'.mb_strtolower(trim($item['unit']));
        $packages = [];
        foreach ($items as $item) {
            if ($key($item) === $key($candidate)) {
                $packages[mb_strtolower(trim($item['package'])).'|'.mb_strtolower(trim($item['unit'])).'|'.(string) $item['wholesale']] = true;
            }
        }

        return count($packages) > 1;
    }
}

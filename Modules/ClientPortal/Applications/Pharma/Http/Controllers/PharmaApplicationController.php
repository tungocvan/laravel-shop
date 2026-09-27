<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\MedicineCatalog;
use Modules\Pharma\Services\UserPriceListWorkspace;
use Modules\Pharma\Services\UserPriceListWorkflow;

final class PharmaApplicationController extends Controller
{
    public function createPriceList(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkflow $workflow,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        return view('ClientPortal::applications.pharma.price-list-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'customers' => $workflow->customers(),
            'purposes' => $workflow->purposes(),
            'products' => $workflow->products($request->string('product_q')->toString()),
            'productSearch' => $request->string('product_q')->toString(),
        ]);
    }

    public function storePriceList(
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'partner_id' => ['required', 'integer'],
            'purpose_id' => ['required', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['nullable'],
            'company_price' => ['required', 'array'],
            'company_price.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect(array_keys($validated['selected']))
            ->map(fn ($variantId): array => [
                'medicine_variant_id' => (int) $variantId,
                'company_sale_price' => $validated['company_price'][$variantId] ?? null,
                'actual_receivable_price' => $validated['company_price'][$variantId] ?? null,
                'invoice_price' => $validated['company_price'][$variantId] ?? null,
            ])->all();

        $list = $workflow->createDraft((int) $user->id, [
            'code' => 'BG-USER-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
            'name' => trim($validated['name']),
            'partner_id' => (int) $validated['partner_id'],
            'purpose_id' => (int) $validated['purpose_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'],
            'currency' => 'VND',
            'priority' => 0,
            'notes' => $validated['notes'] ?? null,
        ], $items);

        return redirect()->route('client.pharma.price-lists.show', $list->id)
            ->with('success', 'Đã lưu bảng giá ở trạng thái Nháp.');
    }

    public function submitPriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.submit'), 403);

        $workflow->submit((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã gửi bảng giá chờ phê duyệt.');
    }


    public function priceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkspace $workspace,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $list = $workspace->findManaged((int) $user->id, $priceList);
        abort_if($list === null, 404);

        return view('ClientPortal::applications.pharma.price-list-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceList' => $list,
            'canSubmit' => $registry->userCan($user, 'client.pharma.price-lists.submit'),
        ]);
    }

    public function priceLists(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:'.implode(',', [
                PriceList::STATUS_DRAFT,
                PriceList::STATUS_PENDING_APPROVAL,
                PriceList::STATUS_ACTIVE,
                PriceList::STATUS_INACTIVE,
                PriceList::STATUS_ARCHIVED,
            ])],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $status = $validated['status'] ?? null;

        return view('ClientPortal::applications.pharma.price-lists', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceLists' => $workspace->browse(
                userId: (int) $user->id,
                search: $validated['q'] ?? null,
                status: $status,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
            )->withQueryString(),
            'counts' => $workspace->counts((int) $user->id),
            'search' => trim((string) ($validated['q'] ?? '')),
            'status' => $status,
            'perPage' => (int) ($validated['per_page'] ?? 25),
            'canCreate' => $registry->userCan($user, 'client.pharma.price-lists.create'),
        ]);
    }

    public function product(
        int $variant,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MedicineCatalog $catalog,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = request()->user('web');
        abort_if($user === null, 401);

        $canViewSupplierPricing = $registry->userCan($user, 'client.pharma.products.supplier-pricing');
        $overview = $catalog->overview($variant, $canViewSupplierPricing);
        abort_if($overview === null, 404);

        return view('ClientPortal::applications.pharma.product-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'product' => $overview['product'],
            'medicine' => $overview['medicine'],
            'profile' => $overview['profile'],
            'awards' => $overview['awards'],
            'suppliers' => $overview['suppliers'],
            'supplierPricingVisible' => $overview['supplier_pricing_visible'],
        ]);
    }

    public function products(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MedicineCatalog $catalog,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'in:awarded,profile,supplier-priced'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $canViewSupplierPricing = $registry->userCan($user, 'client.pharma.products.supplier-pricing');
        $filter = $validated['filter'] ?? null;
        $circularGroup = trim((string) ($validated['group'] ?? ''));
        abort_if($filter === 'supplier-priced' && ! $canViewSupplierPricing, 403);

        return view('ClientPortal::applications.pharma.products', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'products' => $catalog->browse(
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
                filter: $filter,
                allowSupplierPricing: $canViewSupplierPricing,
                circularGroup: $circularGroup !== '' ? $circularGroup : null,
            )->withQueryString(),
            'filter' => $filter,
            'filterCounts' => $catalog->filterCounts($canViewSupplierPricing),
            'circularGroups' => $catalog->circularGroups(),
            'circularGroup' => $circularGroup,
            'canViewSupplierPricing' => $canViewSupplierPricing,
            'search' => trim((string) ($validated['q'] ?? '')),
            'perPage' => (int) ($validated['per_page'] ?? 25),
        ]);
    }

    public function dashboard(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $authorizedFeatures = collect($application['features'] ?? [])
            ->filter(function (array $feature) use ($registry, $user): bool {
                $permission = $feature['permission'] ?? null;

                return $permission === null || $registry->userCan($user, $permission);
            })
            ->values();

        $features = $settings->presentFeatures($application['key'], $authorizedFeatures)
            ->map(function (array $feature): array {
                $routeName = $feature['route'] ?? null;
                $feature['route_available'] = is_string($routeName) && $routeName !== '' && Route::has($routeName);

                return $feature;
            });

        return view('ClientPortal::applications.pharma.dashboard', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'features' => $features,
        ]);
    }
}

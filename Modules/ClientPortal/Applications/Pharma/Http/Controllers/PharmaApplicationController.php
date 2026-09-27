<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\MedicineCatalog;

final class PharmaApplicationController extends Controller
{
    public function product(
        int $variant,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MedicineCatalog $catalog,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $product = $catalog->findVariant($variant);
        abort_if($product === null, 404);

        return view('ClientPortal::applications.pharma.product-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'product' => $product,
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
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.products', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'products' => $catalog->browse(
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
            )->withQueryString(),
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

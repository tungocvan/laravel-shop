<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Livewire\Mechanisms\ComponentRegistry;
use Modules\System\Services\SystemConfigService;

class SystemController extends Controller
{
    public function index(Request $request, SystemConfigService $configService)
    {
        $user = auth('admin')->user() ?: auth()->user();
        $canManage = (bool) $user?->can('system.manage');
        $canViewEnv = (bool) $user?->can('system.env.view');

        abort_unless($canManage || $canViewEnv, 403);

        $registry = app(ComponentRegistry::class);

        $runtimeTabs = $canManage ? $configService->getTabs() : [];
        if ($canViewEnv) {
            // Show shared Mail/Database forms once, in Environment & Integrations.
            $runtimeTabs = array_values(array_filter(
                $runtimeTabs,
                fn (array $tab): bool => ! in_array($tab['id'] ?? null, ['mail', 'database'], true)
            ));
        }

        // Environment access must not implicitly grant runtime operations.
        $environmentTabs = $canViewEnv ? [
            ['id' => 'env-database', 'label' => 'Database', 'component' => 'system.settings.database-config', 'enabled' => true],
            ['id' => 'env-mail', 'label' => 'Email', 'component' => 'system.settings.mail-config', 'enabled' => true],
            ['id' => 'env-advanced', 'label' => 'Runtime & Bridge', 'component' => 'system.settings.advanced-config', 'enabled' => true],
            ['id' => 'env-payment', 'label' => 'Thanh toán', 'component' => 'system.settings.momo-config', 'enabled' => true],
            ['id' => 'env-storage', 'label' => 'Storage / Cloud', 'component' => 'system.settings.storage-config', 'enabled' => true],
            ['id' => 'env-social', 'label' => 'Web & Analytics', 'component' => 'system.settings.social-config', 'enabled' => true],
        ] : [];

        $tabs = collect(array_merge($runtimeTabs, $environmentTabs))
            ->filter(fn ($tab) => $tab['enabled'] ?? true)
            ->map(function ($tab) use ($registry) {
                $component = $tab['component'] ?? null;

                $tab['is_ready'] = is_string($component)
                    && $component !== ''
                    && ! is_null($registry->getClass($component));

                return $tab;
            })
            ->values();

        $requestedTab = trim((string) $request->query('tab', ''));
        $activeTab = $tabs->contains(fn (array $tab): bool => ($tab['id'] ?? null) === $requestedTab)
            ? $requestedTab
            : (string) data_get($tabs->first(), 'id', '');

        return view('System::system', compact('tabs', 'activeTab', 'canManage', 'canViewEnv'));
    }
}

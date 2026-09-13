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
        $this->authorizePermission('system.manage');

        $registry = app(ComponentRegistry::class);

        $tabs = collect($configService->getTabs())
            ->filter(fn ($tab) => $tab['enabled'] ?? true)
            ->map(function ($tab) use ($registry) {
                $tab['is_ready'] = ! is_null($registry->getClass($tab['component']));

                return $tab;
            })
            ->values();

        $requestedTab = trim((string) $request->query('tab', ''));
        $activeTab = $tabs->contains(fn (array $tab): bool => ($tab['id'] ?? null) === $requestedTab)
            ? $requestedTab
            : (string) data_get($tabs->first(), 'id', '');

        return view('System::system', compact('tabs', 'activeTab'));
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}

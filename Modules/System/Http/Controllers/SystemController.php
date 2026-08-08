<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\System\Services\SystemConfigService;

use Livewire\Mechanisms\ComponentRegistry;

class SystemController extends Controller
{
    public function __construct()
    {
       // $this->middleware('permission:system-list|system-create|system-edit|system-delete', ['only' => ['index','show']]);
       // $this->middleware('permission:system-create', ['only' => ['create','store']]);
       // $this->middleware('permission:system-edit', ['only' => ['edit','update']]);
       // $this->middleware('permission:system-delete', ['only' => ['destroy']]);
    }

    public function index(SystemConfigService $configService)
    {
        $this->authorizePermission('system.manage');

        $registry = app(ComponentRegistry::class);

        $tabs = collect($configService->getTabs())
            ->filter(fn ($tab) => $tab['enabled'] ?? true)
            ->map(function ($tab) use ($registry) {
                $tab['is_ready'] = !is_null(
                    $registry->getClass($tab['component'])
                );
                return $tab;
            });

        return view('System::system', compact('tabs'));
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}

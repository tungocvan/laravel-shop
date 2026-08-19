<?php

namespace Modules\ClientPortal\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Spatie\Permission\Models\Role;

class ApplicationAdminController extends Controller
{
    public function index(ApplicationRegistry $registry, ApplicationPermissionService $permissions): View
    {
        return view('ClientPortal::admin.index', [
            'applications' => $registry->all(),
            'definitions' => $permissions->definitions(),
            'users' => User::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function syncPermissions(ApplicationPermissionService $permissions): RedirectResponse
    {
        $created = $permissions->sync();
        return back()->with('success', "Đã đồng bộ permission Client. Tạo mới {$created} permission.");
    }

    public function syncSuperAdmin(ApplicationPermissionService $permissions): RedirectResponse
    {
        $permissions->sync();
        $count = $permissions->syncSuperAdminUsers();
        return back()->with('success', "Đã đồng bộ quyền Client cho {$count} Super Admin.");
    }

    public function editUser(User $user, ApplicationPermissionService $permissions): View
    {
        $permissions->sync();
        return view('ClientPortal::admin.user-permissions', [
            'user' => $user,
            'definitions' => $permissions->definitions(),
            'selected' => $user->permissions->where('guard_name', 'web')->pluck('name')
                ->filter(fn (string $name): bool => str_starts_with($name, 'client.'))->all(),
        ]);
    }

    public function updateUser(Request $request, User $user, ApplicationPermissionService $permissions): RedirectResponse
    {
        $validated = $request->validate(['permissions' => ['sometimes', 'array'], 'permissions.*' => ['string', 'max:255']]);
        $permissions->sync();
        $permissions->syncUser($user, $validated['permissions'] ?? []);
        return back()->with('success', 'Đã cập nhật quyền Application cho User.');
    }

    public function editRole(Role $role, ApplicationPermissionService $permissions): View
    {
        abort_unless($role->guard_name === 'web', 404);
        $permissions->sync();
        return view('ClientPortal::admin.role-permissions', [
            'role' => $role,
            'definitions' => $permissions->definitions(),
            'selected' => $role->permissions->where('guard_name', 'web')->pluck('name')
                ->filter(fn (string $name): bool => str_starts_with($name, 'client.'))->all(),
        ]);
    }

    public function updateRole(Request $request, Role $role, ApplicationPermissionService $permissions): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $validated = $request->validate(['permissions' => ['sometimes', 'array'], 'permissions.*' => ['string', 'max:255']]);
        $permissions->sync();
        $permissions->syncRole($role, $validated['permissions'] ?? []);
        return back()->with('success', 'Đã cập nhật quyền Application cho Role.');
    }
}

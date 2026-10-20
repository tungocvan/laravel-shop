<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnvConfigController extends Controller
{
    /**
     * Canonical environment tab definitions shared with System Center.
     *
     * @return array<int, array{id: string, label: string, component: string, enabled: bool}>
     */
    public static function tabs(): array
    {
        return [
            ['id' => 'env-database', 'label' => 'Cơ sở dữ liệu', 'component' => 'system.settings.database-config', 'enabled' => true],
            ['id' => 'env-mail', 'label' => 'Email', 'component' => 'system.settings.mail-config', 'enabled' => true],
            ['id' => 'env-advanced', 'label' => 'Runtime & Bridge', 'component' => 'system.settings.advanced-config', 'enabled' => true],
            ['id' => 'env-payment', 'label' => 'Thanh toán (Momo)', 'component' => 'system.settings.momo-config', 'enabled' => true],
            ['id' => 'env-storage', 'label' => 'Storage / Cloud', 'component' => 'system.settings.storage-config', 'enabled' => true],
            ['id' => 'env-social', 'label' => 'Web & Analytics', 'component' => 'system.settings.social-config', 'enabled' => true],
        ];
    }

    public function index(Request $request): RedirectResponse
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can('system.env.view'), 403);

        $allowed = array_map(fn (array $tab): string => substr($tab['id'], 4), self::tabs());
        $requested = (string) $request->query('tab', 'database');
        $tab = in_array($requested, $allowed, true) ? $requested : 'database';

        return redirect()->route('admin.system.index', ['tab' => 'env-'.$tab]);
    }
}

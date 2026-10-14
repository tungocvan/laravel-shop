<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\PharmaQuickOrderSettingsService;

final class PharmaQuickOrderController extends Controller
{
    public function index(Request $request, SettingsService $websiteSettings, PharmaQuickOrderSettingsService $quickOrderSettings): View
    {
        abort_unless($websiteSettings->get('website.public_template', 'default') === 'pharma', 404);

        $configuration = $quickOrderSettings->get();
        abort_unless($configuration['enabled'], 404);

        $user = $request->user();
        abort_unless($user && $user->is_active && $user->hasPermissionTo('website.pharma.quick_order.view', 'web'), 403);

        // Customer/partner ownership is not yet established for Website users.
        // Do not expose price-list items, pricing or order actions until this is resolved.
        return view('Website::pages.pharma-quick-order', [
            'items' => collect(),
            'customerLinked' => false,
        ]);
    }
}

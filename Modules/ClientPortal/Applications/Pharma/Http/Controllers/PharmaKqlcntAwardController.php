<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Muasamcong\Models\KqlcntAwardItem;

final class PharmaKqlcntAwardController extends Controller
{
    public function index(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $filters = $request->validate([
            'notify_no' => ['nullable', 'string', 'max:255'],
            'contractor' => ['nullable', 'string', 'max:255'],
            'medicine' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);
        $query = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->when($filters['notify_no'] ?? null, fn ($q, $v) => $q->where('notify_no', 'like', '%'.$v.'%'))
            ->when($filters['contractor'] ?? null, fn ($q, $v) => $q->where('contractor_name', 'like', '%'.$v.'%'))
            ->when($filters['medicine'] ?? null, fn ($q, $v) => $q->where('medicine_name', 'like', '%'.$v.'%'))
            ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'));

        $items = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-awards', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation($application, 'kqlcnt-awards'),
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function show(int $awardItem, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $item = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')->findOrFail($awardItem);
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return view('ClientPortal::applications.pharma.kqlcnt-award-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation($application, 'kqlcnt-awards'),
            'item' => $item,
        ]);
    }
}

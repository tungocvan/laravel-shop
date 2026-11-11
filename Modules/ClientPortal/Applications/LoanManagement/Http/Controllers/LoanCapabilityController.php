<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class LoanCapabilityController extends Controller
{
    private const WORKSPACES = [
        'lenders' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-create'],
        'contracts' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'payments' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'reminders' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'insurance' => ['permission' => 'client.loans.insurance.view', 'anchor' => 'loan-insurance'],
    ];

    public function index(Request $request, string $workspace, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        abort_unless(isset(self::WORKSPACES[$workspace]), 404);
        abort_unless($request->user()?->can(self::WORKSPACES[$workspace]['permission']), 403);

        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);
        $feature = collect($application['features'] ?? [])->firstWhere('key', $workspace);
        abort_unless(is_array($feature), 404);

        return view('ClientPortal::applications.loans.capability', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'feature' => $feature,
            'workspace' => $workspace,
            'legacyAnchor' => self::WORKSPACES[$workspace]['anchor'],
        ]);
    }
}

<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $view = $workspace === 'lenders' ? 'ClientPortal::applications.loans.lenders' : 'ClientPortal::applications.loans.capability';
        $lenders = $workspace === 'lenders' ? DB::table('loan_lenders')->orderBy('name')->get() : collect();

        return view($view, [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'feature' => $feature,
            'workspace' => $workspace,
            'legacyAnchor' => self::WORKSPACES[$workspace]['anchor'],
            'lenders' => $lenders,
        ]);
    }
    public function createContract(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);
        $feature = collect($application['features'] ?? [])->firstWhere('key', 'contracts');
        abort_unless(is_array($feature), 404);

        return view('ClientPortal::applications.loans.contract-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'feature' => $feature,
            'lenders' => DB::table('loan_lenders')->orderBy('name')->get(),
        ]);
    }

}

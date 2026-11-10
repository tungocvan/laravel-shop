<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class LoanApplicationController extends Controller
{
    public function dashboard(ApplicationRegistry $registry, ClientPortalSettingsService $settings, LoanContractService $loans): View
    {
        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);

        $feature = collect($application['features'] ?? [])->firstWhere('key', 'overview');
        $presentation = $feature ? $settings->featurePresentation('loans', $feature) : [];

        return view('ClientPortal::applications.loans.dashboard', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'overviewPresentation' => $presentation,
            'loanData' => $loans->dashboard(),
        ]);
    }
}

<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Services\TraditionalDrugRegistrationLookup;

final class PharmaTraditionalDrugRegistrationController extends BasePharmaRegistrationController
{
    public function index(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        TraditionalDrugRegistrationLookup $lookup
    ): View {
        return $this->indexFor($request, $registry, $settings, $lookup, 'traditional-drug-registrations', 'client.pharma.traditional-drug-registrations');
    }

    public function show(
        Request $request,
        string $sourceId,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        TraditionalDrugRegistrationLookup $lookup
    ): View {
        return $this->showFor($request, $sourceId, $registry, $settings, $lookup, 'traditional-drug-registrations', 'client.pharma.traditional-drug-registrations');
    }
}

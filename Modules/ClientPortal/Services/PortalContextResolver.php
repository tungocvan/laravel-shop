<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;

class PortalContextResolver
{
    public function __construct(
        private readonly PortalAccessResolver $access,
        private readonly PortalNavigationResolver $navigation
    ) {
    }

    public function resolve(?User $user): array
    {
        $applications = $this->access->applicationsFor($user)->values();
        $singleApplication = $applications->count() === 1 ? $applications->first() : null;

        return [
            'user_id' => $user?->getKey(),
            'applications' => $applications,
            'application_count' => $applications->count(),
            'single_application' => $singleApplication,
            'requires_application_selection' => $applications->count() > 1,
            'has_access' => $applications->isNotEmpty(),
        ];
    }

    public function applicationContext(array $application, ?User $user): array
    {
        return [
            'application' => $application,
            'navigation' => $this->navigation->forApplication($application, $user),
            'quick_actions' => $this->navigation->quickActionsFor($application, $user),
            'capabilities' => $application['capabilities'] ?? [],
            'layout' => $application['layout'] ?? ['mode' => 'standard'],
        ];
    }
}

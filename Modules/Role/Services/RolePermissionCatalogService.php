<?php

namespace Modules\Role\Services;

use App\Modules\ModulePermissionManager;

class RolePermissionCatalogService
{
    public function __construct(
        private readonly ModulePermissionManager $modulePermissions,
    ) {
    }

    public function previewActiveSync(): array
    {
        return $this->modulePermissions->previewActiveSync();
    }

    public function syncAllActiveToSuperAdmin(): array
    {
        return $this->modulePermissions->syncAllActiveToSuperAdmin();
    }
}

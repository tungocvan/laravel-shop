# System Collaboration Handoff

## Current Status

- Module: `System`
- Feature: System settings ownership and Admin compatibility adapters
- Delivery branch: `refactor/system-settings-ownership-adapters`
- Base/source checkpoint: `main@ee8e313635d759e91658de7e751ecabbc0e96c4f`
- Implementation checkpoint: pending operator verification
- Implementation status: **IMPLEMENTED — OPERATOR VERIFICATION PENDING**
- Pull request: pending operator verification

This phase consolidates settings behavior under `Modules\System` without breaking the established Admin PHP class names or the historical `/admin/settings` URL. It follows the merged Google Drive/database-backup boundary phase and does not change database schemas, setting keys, permissions, or canonical System routes.

## Approved Scope

- replace duplicate Admin settings Livewire implementations with thin deprecated adapters that extend their System counterparts;
- replace duplicate Admin environment/database services with thin deprecated adapters that extend System services;
- retain the old Admin settings controllers as redirect-only compatibility adapters;
- redirect `/admin/settings` to `/admin/system/settings` behind `system.settings.view`;
- seed new Website admin-menu rows with the canonical settings URL;
- repair Admission's settings-model import to use `Modules\System\Models\Setting`;
- remove obsolete Admin settings views after their adapters render the canonical System views;
- update directly impacted architecture and module-runtime tests.

## Ownership Contract

System owns all active settings behavior:

| Concern | Canonical owner | Compatibility boundary |
|---|---|---|
| Settings Livewire screens | `Modules\System\Livewire\Settings` | Existing `Modules\Admin\Livewire\Settings` names extend the System classes |
| Environment services | `Modules\System\Services\Env` | Existing Admin service names extend the System classes |
| Database connection test | `Modules\System\Services\Database\DbConnectionService` | Admin class remains as an adapter |
| Settings pages | `/admin/system/settings` and `/admin/system/settings/env` | Old controllers and `/admin/settings` redirect to canonical routes |
| Settings model | `Modules\System\Models\Setting` | No Admin model alias is introduced |

The Admin layout/theme/header settings classes and views remain Admin-owned. The intentionally active `admin.theme-switcher` and `admin.header.menu-manager` components are unchanged.

## Compatibility Details

Preserved:

- nine legacy Admin Livewire class names for external/custom references;
- six legacy Admin service class names;
- `SettingController::index`, `SettingController::profile`, `SettingController::modules`, and `EnvConfigController::index` entry points;
- existing production links to `/admin/settings` through a permission-protected redirect;
- all canonical System route names, permission names, setting keys, OAuth routes and backup behavior.

Retired:

- duplicate Admin settings component logic and views;
- duplicate Admin environment/database service logic;
- four unused Admin settings page views;
- the broken Admin `Placeholder` Livewire class and placeholder page. The class referenced a nonexistent component view and had no repository consumer; the corresponding System placeholder had already been retired.

## Data and Migration Boundary

No migration, schema or stored-setting transformation is included. The historical Admin migration that creates `settings` remains in place for migration-history compatibility. Existing Header menu rows are not rewritten; their legacy URL remains functional through the redirect. A future seeder run writes the canonical URL directly.

## Files

### Added

```text
tests/Feature/System/SystemSettingsOwnershipTest.php
```

### Updated

```text
Modules/Admin/Http/Controllers/EnvConfigController.php
Modules/Admin/Http/Controllers/SettingController.php
Modules/Admin/Livewire/Settings/*.php (nine compatibility adapters)
Modules/Admin/Services/Database/DbConnectionService.php
Modules/Admin/Services/Env/*.php (five compatibility adapters)
Modules/Admission/Livewire/Admin/SchoolSettingsForm.php
Modules/System/routes/web.php
Modules/Website/database/Seeders/HeaderSeeder.php
tests/Feature/Modules/ModuleRuntimeStateToggleTest.php
tests/Feature/System/CanonicalSettingsServiceTest.php
```

### Removed

```text
Modules/Admin/Livewire/Settings/Placeholder.php
Modules/Admin/resources/views/livewire/settings/{advanced-config,database-config,env-manager,mail-config,modules-form,momo-config,setting-form,social-config,storage-config}.blade.php
Modules/Admin/resources/views/pages/settings/{env,index,modules,placeholder}.blade.php
```

## Verification Gate

Run only the directly impacted and current-module suites; a full-project run is not required.

```bash
./vendor/bin/pint --test \
  Modules/Admin/Http/Controllers/EnvConfigController.php \
  Modules/Admin/Http/Controllers/SettingController.php \
  Modules/Admin/Livewire/Settings \
  Modules/Admin/Services/Database/DbConnectionService.php \
  Modules/Admin/Services/Env \
  Modules/Admission/Livewire/Admin/SchoolSettingsForm.php \
  Modules/System/routes/web.php \
  Modules/Website/database/Seeders/HeaderSeeder.php \
  tests/Feature/Modules/ModuleRuntimeStateToggleTest.php \
  tests/Feature/System/CanonicalSettingsServiceTest.php \
  tests/Feature/System/SystemSettingsOwnershipTest.php

php artisan test \
  tests/Feature/System/SystemSettingsOwnershipTest.php \
  tests/Feature/System/CanonicalSettingsServiceTest.php \
  tests/Feature/System/SystemRetiredSettingsPlaceholdersTest.php \
  tests/Feature/Modules/ModuleRuntimeStateToggleTest.php

php artisan test tests/Feature/System
php artisan test tests/Feature/Admin
php artisan test tests/Feature/Admission

php artisan route:list --path=admin/settings
php artisan route:list --path=admin/system/settings
npm run build
git status --short --branch
```

Manual UI acceptance:

1. Open `/admin/settings` as a settings viewer and confirm redirect to `/admin/system/settings`.
2. Confirm a user without `system.settings.view` cannot use the legacy URL.
3. Verify settings and environment tabs on desktop and mobile.
4. Verify module controls still use the canonical System permission/service boundary.
5. Confirm Admin layout/theme/header screens remain unchanged.

## Deferred Work

- Decide whether the long-term settings contract belongs in Shared after the current System ownership stabilizes.
- Separate Module registry discovery from runtime mutation.
- Improve scheduler idempotency, distributed locking and persisted health heartbeat behavior.
- Split database operations behind smaller services beyond the completed backup catalog boundary.
- Consolidate historical System analysis documents after implementation phases settle.

## PR and Merge Gate

1. **COMPLETE** — Scope approved and branch created from `main@ee8e313635d759e91658de7e751ecabbc0e96c4f`.
2. **COMPLETE** — Ownership adapters, redirect boundary, dead-view cleanup and directly impacted tests implemented.
3. **PENDING** — Operator pulls the implementation checkpoint and runs the approved gates.
4. **PENDING** — Verification results and verified checkpoint are recorded here.
5. **PENDING** — PR is opened for manual user review; automatic merge is not used.
6. **PENDING** — User manually merges after approval.

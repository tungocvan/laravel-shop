# Settings/ModulesForm Livewire Analysis

Analysis date: 2026-08-12

Implementation status: **Refactored 2026-08-12**

Scope: `Modules/System/Livewire/Settings/ModulesForm.php`, direct services/UI, route and Admin Menu boundary.

## Executive Summary

The previous P0 authorization gap has been remediated. `Settings/ModulesForm` remains the System module control-plane page at `/admin/system/modules`, visible with `system.modules.view`, while every state-changing Livewire action now enforces `system.modules.update` at the action boundary.

High-impact module lifecycle orchestration has moved out of Livewire into `Modules/System/Services/SystemModuleControlService.php`. The service re-resolves canonical module registry state, serializes lifecycle mutations with a per-module cache lock, preserves dependency/required-module checks, performs migration before permission synchronization, writes the manifest only after those stages succeed, updates runtime config, archives through the existing `ModuleLifecycleManager`, and logs safe operation metadata.

The existing Admin Menu entry was normalized instead of duplicated:

- Name: `Quản lý Modules`
- URL: `/admin/system/modules`
- Permission: `system.modules.view`

## Current Dependency Flow

```text
GET /admin/system/modules
  -> auth:admin
  -> system.modules.view
  -> Settings/ModulesForm

Read workflows
  -> ModuleLifecycleManager::databaseStatus()
  -> RealtimeManager::health()
  -> ModuleRouteManager::rows()

Mutation workflows
  -> authorize system.modules.update
  -> SystemModuleControlService
      -> per-module Cache::lock
      -> canonical config('modules.registry')
      -> dependency/required preflight
      -> ModuleLifecycleManager::migrateIfNeeded()
      -> ModulePermissionManager::sync()
      -> manifest enabled flag write
      -> runtime config update
      -> structured logging

Route title/menu mutations
  -> authorize system.modules.update
  -> ModuleRouteManager
```

## Authorization Status

Resolved P0 issue:

- route/menu visibility: `system.modules.view`;
- `toggleRealtime()`: `system.modules.update`;
- `toggleModule()`: `system.modules.update`;
- `deleteModule()`: `system.modules.update`;
- `saveRouteTitle()`: `system.modules.update`;
- `addRouteToMenu()`: `system.modules.update`.

The UI also exposes a read-only mode for admins without update permission, but server authorization remains authoritative.

## Livewire Responsibility After Refactor

`ModulesForm` now owns:

- UI/read state;
- route search/filter/edit state;
- read-only status refresh;
- mutation authorization;
- delegation to domain/control services;
- sanitized browser feedback.

Removed from Livewire:

- direct `File::put()` manifest writes;
- migration orchestration;
- permission-sync ordering;
- archive orchestration;
- raw internal exception detail in mutation error messages.

Route title now has explicit Livewire validation: required, string, max 255.

## SystemModuleControlService

New service responsibilities:

- canonical registry lookup by module name;
- per-module lock with key prefix `system:module-control:`;
- required shell-module protection;
- dependency checks before enable;
- reverse-dependency checks before disable;
- migration stage before permission stage;
- manifest write only after migration/permission success;
- runtime registry update;
- permission cache cleanup on disable;
- archive through existing lifecycle safeguards;
- realtime mutation wrapper with structured logging;
- safe operation result arrays for the UI.

The service intentionally does not claim that migrations can be rolled back atomically. A failure after a migration can still represent a partial infrastructure transition; detailed failure information is logged server-side instead of being exposed to the browser.

## Manifest Handling

Manifest writes now occur only in the control service and are derived from the canonical registry module path. Compatibility remains for both:

- `config/module.php`
- `Config/module.php`

The service requires a writable array manifest, changes only the `enabled` value, writes the PHP array back, clears stat cache and invalidates opcache when available.

## Concurrency

Toggle and archive operations are serialized with a per-module cache lock. If another operation already holds the module lock, the operator receives a controlled retry-later message.

UI loading/disabled state remains defense-in-depth and is not relied upon for concurrency safety.

## Error Handling / Auditability

Detailed exceptions are logged with safe metadata such as actor, module, operation, target state and exception class.

Livewire no longer appends internal exception messages for migration, filesystem, permission, realtime or route-title failures. Known domain/preflight messages such as dependency restrictions remain safe and actionable.

## Route Manager Performance

`ModuleRouteManager::rows()` now preloads Admin Menu URLs once and resolves `in_menu` in memory instead of issuing an `AdminMenu::exists()` query for every route. This removes the previously identified per-route menu N+1 pattern.

## UI / UX

Improvements include:

- enable/disable confirmation;
- migration/permission warning when enabling;
- archive confirmation retained;
- loading/disabled mutation controls;
- read-only UI when `system.modules.update` is absent;
- realtime mutation confirmation;
- route-title max-length/UI validation feedback;
- route-title/add-menu mutation disablement for view-only admins;
- no raw database/internal error details in module cards.

## Admin Menu

Canonical source `Modules/Admin/data/menus.json` now contains exactly one Modules entry under `Công cụ Hệ thống`:

```text
Quản lý Modules
/admin/system/modules
system.modules.view
```

Existing installations with populated `admin_menus` may require a targeted idempotent update because the current menu seeder does not overwrite populated data.

## Tests

Added:

`tests/Feature/System/SystemModulesControlTest.php`

Coverage includes:

- route/menu view permission;
- mutation authorization contract;
- required module disable prevention;
- disabled dependency enable prevention;
- enabled dependent disable prevention;
- migration failure stops permission/manifest stages;
- permission-sync failure does not enable manifest;
- successful lifecycle writes manifest after prior stages;
- per-module lock contract;
- route-title validation;
- route-list menu URL preload regression check;
- sanitized browser error contract.

## Remaining Risks / Follow-up

- Database migrations are inherently not part of one atomic transaction with filesystem manifest and permission changes. Persistent cross-system transaction/audit infrastructure remains future platform work.
- Realtime health is still synchronous during page status refresh and may add latency when its endpoint is slow.
- Archive source-of-truth/bootstrap behavior after moving a module directory remains owned by the existing registry/bootstrap implementation and should be reviewed separately if archive semantics are expanded.

## Refactor Decision

**Major refactor complete for the approved scope.**

Do not move lifecycle orchestration back into Livewire. Future module lifecycle mutations should continue through the control service (or a future canonical transaction framework) and must preserve action-level `system.modules.update` authorization.

# Settings/ModulesForm Livewire Analysis

Analysis date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ModulesForm.php`, its Blade view, route boundary, and directly called services. Documentation-only analysis; application source code is unchanged.

## Executive Summary

`Settings/ModulesForm` is an active privileged control-plane component for module lifecycle, permission synchronization, realtime enablement, route title management, and admin-menu registration. Unlike `ArtisanList` and `ShScript`, it is not production-disabled: `/admin/system/modules` mounts this functionality behind `system.modules.view`.

The component performs several high-impact mutations—enabling/disabling modules, running module migrations, synchronizing permissions, archiving module source code, rewriting module manifest PHP files, toggling realtime, updating route titles, and creating admin-menu records—without enforcing `system.modules.update` or another mutation-specific permission at the Livewire action boundary.

This is a **P0 production-control authorization gap**. The module manifest already defines `system.modules.update`, so the intended permission exists but is not used by the component. The component should be refactored rather than rebuilt from scratch: its dependency checks, shell-module protection, migration helper, archive path validation, and permission synchronization already provide useful safety behavior.

Recommended direction: **major component refactor focused on action-level authorization, service-layer consolidation, transaction/rollback semantics, confirmations, audit logging, and tests.**

## Component Purpose

- PHP: `Modules/System/Livewire/Settings/ModulesForm.php`
- Blade: `Modules/System/resources/views/livewire/settings/modules-form.blade.php`
- Alias: `system.settings.modules-form`
- Page: `/admin/system/modules`
- Route permission: `system.modules.view`
- Manifest permissions relevant to this component:
  - `system.modules.view`
  - `system.modules.update`
  - `system.manage`

Responsibilities currently include:

- load module registry/status;
- detect dependencies and reverse dependencies;
- inspect expected database tables;
- enable/disable modules;
- run pending module migrations when enabling;
- synchronize module permissions;
- rewrite module manifest `enabled` flag;
- archive disabled modules into `storage/app/module-trash`;
- toggle realtime feature state;
- inspect realtime health;
- enumerate module routes;
- edit route titles;
- add routes to admin menu.

This is broader than a simple “module toggle” UI and effectively acts as a system control plane.

## Dependency Flow

```text
GET /admin/system/modules
  -> permission:system.modules.view
  -> SettingController::modules
  -> page Blade
  -> system.settings.modules-form

ModulesForm
  -> config('modules.registry')
  -> App\Modules\ModuleLifecycleManager
       -> Schema
       -> Artisan migrate --force
       -> File moveDirectory -> storage/app/module-trash
  -> App\Modules\ModulePermissionManager
       -> Spatie Permission / Role
  -> App\Services\RealtimeManager
       -> Admin Setting model
       -> realtime health HTTP endpoint
  -> Modules\Admin\Services\ModuleRouteManager
       -> Laravel route collection
       -> ModuleRouteTitle model
       -> AdminMenu model
  -> File facade
       -> Modules/<Module>/config/module.php rewrite
```

## Livewire PHP Analysis

### Public state

- `$modules`
- `$realtimeEnabled`
- `$realtimeStatus`
- `$moduleRoutes`
- `$editingRouteKey`
- `$routeTitle`
- `$routeSearch`
- `$routeModuleFilter`

### Lifecycle

`mount()` performs three potentially non-trivial operations:

1. `loadModules()`
2. `refreshRealtimeStatus()`
3. `loadModuleRoutes()`

`loadModules()` iterates every configured module, computes reverse dependencies, and calls `ModuleLifecycleManager::databaseStatus()` for each module. `loadModuleRoutes()` enumerates registered routes and performs database lookups through `ModuleRouteManager`.

### Mutations

- `toggleRealtime()` changes persisted realtime state.
- `toggleModule()` may run migrations, sync permissions, and rewrite a manifest.
- `deleteModule()` moves module source code into archive storage.
- `saveRouteTitle()` writes route-title metadata.
- `addRouteToMenu()` creates an admin-menu record.

None of these methods call an authorization helper.

### Business logic in Livewire

The component contains significant workflow logic directly:

- dependency validation;
- reverse-dependency checks;
- required-module rules;
- enable/disable flow orchestration;
- manifest rewriting;
- permission synchronization order;
- in-memory registry mutation;
- success-message assembly.

This exceeds the repository standard's preferred Livewire responsibility and makes transaction/rollback behavior difficult to reason about.

## Livewire Blade Analysis

Positive behavior:

- module state is grouped by type;
- required shell modules are disabled in UI;
- dependencies and reverse dependencies are displayed;
- missing database tables are surfaced;
- delete/archive uses `wire:confirm`;
- enabled modules cannot be deleted through normal UI;
- responsive grid/list structure is used.

Material concerns:

- module enable/disable is a high-impact action but has no confirmation;
- realtime toggle is rendered through `x-realtime-control`; action authorization is not visible at the parent component boundary;
- route-title/menu mutations are delegated to `x-module-routes-table`, but backend action guards remain absent;
- mutation loading/disabled states are not clearly enforced for module toggle/archive operations;
- Blade contains an `@php` grouping/label block; this is presentation-only logic but could be simplified into computed/view data for consistency.

## State / Validation / Actions

Validation is limited:

- `routeTitle` has no Livewire validation rule before passing to `ModuleRouteManager::saveTitle()`; the service trims, rejects empty text and limits storage to 255 chars.
- `moduleName` and route keys are resolved against server-loaded state before mutation, which is positive.
- manifest path is derived from registry state rather than arbitrary user path input.

Server-side state checks reduce tampering risk, but they do not replace authorization.

## Authorization

### P0 — View permission is used as mutation boundary

Route:

```text
/admin/system/modules
  -> permission:system.modules.view,admin
```

Manifest:

```text
system.modules.view
system.modules.update
```

Component mutations do not call `system.modules.update`.

This is a direct mismatch between the defined capability model and implementation. Repository `MODULE_STANDARD.md` requires sensitive mutations to enforce authorization at the action boundary.

Affected actions:

- `toggleRealtime()`
- `toggleModule()`
- `deleteModule()`
- `saveRouteTitle()`
- `addRouteToMenu()`

`refreshRealtimeStatus()`, `loadModules()`, `loadModuleRoutes()` and edit-state methods are read-only or local state and can remain under view permission as appropriate.

### Permission granularity consideration

`system.modules.update` is sufficient as an immediate remediation for module lifecycle actions, but realtime/menu-route mutations may ultimately deserve separate canonical permissions if the project wants least privilege. This is a design decision for refactor planning, not a prerequisite to documenting the current gap.

## Service / Model Dependencies

### `ModuleLifecycleManager`

Positive safety behavior observed:

- required modules cannot be archived;
- enabled modules cannot be archived;
- dependents block archive;
- archive source is canonicalized with `realpath()` and verified under the `Modules` root;
- enabling may run module migrations with `--force` and `--no-interaction`;
- expected database tables are checked after migration.

Risk/architecture notes:

- migrations are production-destructive/control-plane operations and need explicit authorization + audit;
- archive moves source code but does not provide a multi-step transaction with manifest/registry/menu/permissions.

### `ModulePermissionManager`

Positive behavior:

- permissions are loaded from module manifest;
- permission cache is cleared;
- missing permissions are created for `admin` guard;
- Super Admin receives module permissions.

Risk:

- permission synchronization happens before manifest write. If manifest update fails afterward, permissions may already be changed, leaving partial state.

### `RealtimeManager`

- persists `realtime_enabled` through `Modules\Admin\Models\Setting`;
- respects global `realtime.allowed` when enabling;
- health check uses a configured endpoint and timeout.

This is a cross-module dependency from System into Admin-owned settings.

### `ModuleRouteManager`

- route title update uses `updateOrCreate`;
- menu addition blocks dynamic routes and duplicates;
- permission is extracted from route middleware and stored in menu `can` field.

These are useful service boundaries, but `ModulesForm` itself still orchestrates authorization and state without guards.

## Transactions / Concurrency / Data Integrity

### P1 — Partial-state risk during module toggle

Current enable flow can perform:

1. migration;
2. permission synchronization;
3. manifest rewrite;
4. in-memory config update.

These operations span database schema, permission rows/cache, filesystem manifest and runtime config. They cannot be wrapped in one ordinary database transaction.

Failure after migration or permission sync can leave the system in a partially transitioned state.

Recommended direction:

- define explicit lifecycle operation stages and compensating rollback where feasible;
- persist/audit operation result;
- treat migration as irreversible/high-risk step;
- write manifest at a deliberate stage with recovery semantics;
- serialize lifecycle changes with a lock to prevent concurrent toggles for the same module.

### P1 — Archive operation consistency

`deleteModule()` moves module source to `storage/app/module-trash` and then removes it only from the in-memory registry for the current request. The source-of-truth registry reconstruction behavior after restart/request should be verified during refactor planning.

### P1 — No concurrency guard

Two administrators could attempt lifecycle changes simultaneously. There is no lock/idempotency key around module enable/disable/archive operations.

## Performance

Potential concerns:

- `mount()` calls database-status checks for every module;
- `ModuleRouteManager::rows()` loops over routes and executes an `AdminMenu::exists()` query per route, creating an N+1 query pattern;
- module database status calls `Schema::hasTable()` per expected table;
- realtime health performs an HTTP call during mount through `refreshRealtimeStatus()`.

These may be acceptable for a small control-plane page but should be measured/bounded as the module count and route count grow.

Priority: P1/P2 depending on observed production latency.

## Security / Data Integrity

### P0-1 — Missing mutation authorization

**Evidence:** route requires only `system.modules.view`; component does not enforce `system.modules.update` although that permission exists in manifest.

**Impact:** a principal intended to have read-only module access may reach high-impact mutation actions if Livewire action invocation is available within the rendered component session.

**Recommendation:** enforce `system.modules.update` at every state-changing action immediately.

### P0-2 — Source-code/control-plane mutation from Livewire

**Evidence:** component rewrites module manifest PHP files and can archive module source.

**Impact:** production application topology/code availability can change from a web session.

**Recommendation:** retain only with strict authorization, audit, locking, confirmation and recovery semantics. Consider separating archive/removal into a stronger capability than ordinary module update.

### P1-1 — Partial lifecycle transitions

**Evidence:** migration, permission sync and filesystem writes are separate steps with no compensating framework.

**Impact:** failed enable/disable can leave schema/permissions/manifest out of sync.

### P1-2 — Error-message exposure

Several caught exceptions are shown directly through session flash messages, including migration/database/module filesystem errors.

**Impact:** may reveal internal paths or infrastructure details.

**Recommendation:** log detailed exceptions server-side and return sanitized operator messages with an operation/audit ID.

## UI/UX Compliance

Positive:

- responsive layout;
- dependency state is visible;
- required modules are protected in UI;
- archive/delete has confirmation;
- database readiness is surfaced.

Needs improvement:

- enable/disable should use confirmation when the operation may migrate database or change permissions;
- loading/disabled state should prevent repeated lifecycle mutations;
- dangerous/archive actions should communicate consequences consistently;
- health checks should avoid making page render feel blocked if endpoint is slow;
- mutation permissions should drive UI availability in addition to server-side enforcement.

## Test Coverage

No System-specific feature/unit tests for this component were found in the inspected repository test trees.

High-priority test cases:

- `system.modules.view` without `system.modules.update` can render but cannot mutate;
- update permission allows expected actions;
- required shell module cannot be disabled/archive even with crafted Livewire state;
- enabled module cannot be archived;
- dependency and reverse-dependency rules are enforced server-side;
- failed migration does not mark module enabled;
- failed permission sync does not rewrite manifest;
- failed manifest write produces a recoverable/observable state;
- concurrent lifecycle actions are serialized/rejected;
- route title/menu actions require update permission;
- realtime mutation requires appropriate update permission;
- archived path always remains under the canonical module-trash root;
- errors shown to browser are sanitized.

## Issue List

### P0 — Missing `system.modules.update` action authorization

**File:** `Modules/System/Livewire/Settings/ModulesForm.php`

**Evidence:** manifest defines update permission; route uses view permission; mutation methods contain no authorization calls.

**Problem:** read access and control-plane mutation are not separated at the Livewire boundary.

**Impact:** module topology, migrations, permissions, source archive, realtime state and menu configuration may be changed without the intended update capability check.

**Recommendation:** add canonical action-level authorization to every mutation.

### P0 — Web control of module source/application topology

**Files:** `ModulesForm.php`, `ModuleLifecycleManager.php`.

**Evidence:** manifest files are rewritten; module directories can be moved into archive storage.

**Impact:** application availability and production topology can change from admin UI.

**Recommendation:** keep only behind explicit high-trust capability, audit, confirmation, lock and recovery policy.

### P1 — Multi-system lifecycle is not atomic

**Evidence:** schema migration, permission sync, manifest write and runtime config update are separate operations.

**Impact:** partial state after failure.

**Recommendation:** introduce a lifecycle orchestration service with explicit stages and compensating recovery.

### P1 — Concurrent operations are not serialized

**Recommendation:** use a per-module lock around lifecycle mutation.

### P1/P2 — Route enumeration N+1

**Evidence:** `ModuleRouteManager::rows()` checks menu existence with a query inside the route loop.

**Recommendation:** preload relevant menu URLs once.

## Recommended Direction

**Major Refactor — retain functionality, move control-plane workflow out of Livewire.**

Target flow:

```text
Livewire ModulesForm
  -> authorize system.modules.update (or stronger granular capability)
  -> validate/confirm operation
  -> ModuleLifecycleService
       -> lock module
       -> preflight dependencies/database
       -> migration stage
       -> permission stage
       -> manifest/runtime stage
       -> audit outcome
       -> recovery/compensation when possible
```

Read-only module status can remain available under `system.modules.view`.

Suggested capability separation during implementation planning:

- `system.modules.view`
- `system.modules.update`
- optional stronger `system.modules.archive`
- separate realtime/menu permissions only if repository permission strategy supports that granularity consistently.

## Open Questions / Unknowns

- How the global module registry is rebuilt after a module directory is archived should be verified against `Modules\ModuleServiceProvider`/registry bootstrap before changing archive semantics.
- Whether realtime mutation belongs in System Modules or a dedicated settings/control component is an ownership question for the refactor phase.
- The repository's canonical audit-log implementation for production-control operations was not identified within this component-level scope.
- Runtime behavior after manifest rewrite under long-lived PHP workers/Octane-like deployments should be verified if such deployment mode is used.

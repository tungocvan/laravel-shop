# Settings/ModulesForm Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ModulesForm.php`, its direct services/UI/tests, and normalization of the existing Admin Menu entry for `/admin/system/modules`.

Status: **Awaiting explicit approval before implementation.**

## 1. Refactor Goal

Refactor the existing System module control plane so read access and mutation access are separated correctly, high-impact module lifecycle workflows leave Livewire and move behind explicit service orchestration, mutation errors are sanitized, repeated/concurrent lifecycle changes are guarded, and the existing Admin Menu entry is aligned with canonical route/permission rules.

This is a major refactor, not a rebuild. Existing useful dependency/migration/archive/permission-sync protections should be retained.

## 2. Existing Route and Admin Menu

Canonical route already exists:

```text
GET /admin/system/modules
name: admin.system.modules
middleware: auth:admin + permission:system.modules.view,admin
```

Therefore no new route/page is required.

Existing canonical Admin Menu entry currently has two defects:

- URL is `admin/system/modules` instead of `/admin/system/modules`;
- capability is legacy `create_admin` instead of `system.modules.view`.

Implementation will update the existing entry rather than create a duplicate:

```text
Name: Quản lý Modules
URL: /admin/system/modules
Can: system.modules.view
Active: true
```

For existing databases, provide an idempotent `updateOrCreate`/targeted update instruction scoped to this one menu item. Do not reset/reseed the full menu table.

## 3. Authorization Model

Keep route/menu visibility under:

`system.modules.view`

Every state-changing Livewire action must enforce:

`system.modules.update`

using `AuthorizesSystemActions`.

Mutation actions requiring update permission:

- `toggleModule()`
- `deleteModule()`
- `toggleRealtime()`
- `saveRouteTitle()`
- `addRouteToMenu()`

Read/local-state actions remain available under page view permission.

No new permission is required for the initial refactor because both `system.modules.view` and `system.modules.update` already exist in the System manifest.

## 4. Livewire Responsibility

`ModulesForm` should be reduced to:

- page/UI state;
- search/filter/edit state;
- calling read services;
- authorization at mutation boundary;
- invoking one lifecycle/control service;
- refreshing state;
- sanitized success/error feedback.

Remove from Livewire:

- direct module manifest file writes;
- lifecycle dependency orchestration;
- migration orchestration;
- permission-sync ordering;
- module archive orchestration;
- raw exception detail exposure.

`updateModuleManifest()` should no longer live in the component.

## 5. Module Lifecycle Orchestration Service

Create a narrowly scoped service, proposed:

`Modules/System/Services/SystemModuleControlService.php`

Responsibilities:

### Toggle module

1. resolve the module from canonical `config('modules.registry')`, not browser state;
2. acquire per-module lock;
3. validate required/shell module restrictions;
4. validate dependencies or reverse dependencies;
5. if enabling, run `ModuleLifecycleManager::migrateIfNeeded()`;
6. sync permissions only after migration succeeds;
7. update module manifest using a dedicated manifest method/service;
8. update runtime config;
9. clear permission cache where required;
10. log structured operation outcome;
11. release lock.

The service should return an explicit result DTO/array with safe display metadata rather than relying on session flash inside lower services.

### Archive module

1. acquire per-module lock;
2. re-resolve canonical module state;
3. rely on existing `ModuleLifecycleManager::archive()` protections;
4. update runtime registry only after archive succeeds;
5. log safe result metadata.

Do not introduce permanent deletion. Preserve current archive-to-trash behavior.

## 6. Manifest Writes / Recovery

Move manifest mutation into the service layer.

Requirements:

- derive manifest path only from canonical module registry data;
- support `config/module.php` and `Config/module.php` compatibility only if still needed;
- verify writable file;
- read current manifest before modification;
- write only the `enabled` flag while preserving other manifest values;
- invalidate stat/opcache as currently done;
- log detailed server-side failure but return generic operator-safe error.

Because migration/database changes cannot be rolled back reliably by ordinary DB transaction after execution, the service must model stages explicitly.

Recommended stage ordering for enable:

```text
preflight
→ migration
→ permission sync
→ manifest/runtime enable
```

If manifest update fails after migration/permission sync, do not pretend a full rollback occurred. Log a partial-transition failure and keep the browser message generic but actionable (for example: operation failed after preflight; inspect logs/operation state).

Future persistent transaction/audit framework can improve this further; it is outside this component-level refactor.

## 7. Concurrency

Add a per-module cache lock around toggle/archive operations.

Suggested key:

`system:module-control:<module-name>`

Use a conservative expiry and short acquisition window.

If lock acquisition fails, return a controlled message that another module operation is already running.

UI loading state is defense-in-depth only and does not replace server locking.

## 8. Realtime Mutation

Keep existing `RealtimeManager` for the actual persisted setting and health behavior.

`toggleRealtime()` must:

- enforce `system.modules.update` for this refactor scope;
- delegate mutation to a service/helper rather than expose raw exception text;
- refresh status after success;
- log safe actor/result metadata.

Longer term, realtime ownership may move to a dedicated settings component, but that is not required here.

## 9. Route Title / Admin Menu Mutation

Keep `ModuleRouteManager` as the direct domain service for route title/menu records.

`ModulesForm` must enforce `system.modules.update` before:

- `saveRouteTitle()`
- `addRouteToMenu()`

Also add explicit Livewire validation for route title before calling the service:

- string
- required
- max:255

Errors returned to UI must be generic; detailed exception text goes to logs.

## 10. Read Performance

Within approved scope, address the obvious route-table N+1 if `ModuleRouteManager::rows()` currently checks AdminMenu existence per route.

Preferred change:

- preload menu URLs once;
- resolve `in_menu` in memory while enumerating routes.

Do not redesign module registry/bootstrap in this task.

Realtime health remains potentially network-bound; do not add asynchronous polling unless required by current architecture/tests.

## 11. Blade / UX

Preserve current module grouping/dependency/status UI.

Improve mutation UX:

- add confirmation for enable/disable module actions;
- confirmation text should mention migrations/permissions when enabling;
- keep archive/delete confirmation;
- add `wire:loading.attr="disabled"` and targets for module lifecycle mutations;
- disable mutation controls in UI when current admin lacks `system.modules.update`, while still relying on server authorization;
- keep required shell modules visibly protected;
- do not show raw exception details;
- route-title save and add-menu buttons should have loading/disabled feedback.

No design-system rebuild is required.

## 12. Logging / Auditability

Use structured Laravel application logging consistent with the System operation refactors.

Log safe metadata:

- actor/admin ID;
- operation type;
- module name;
- target enabled state;
- lifecycle stage/result;
- migration performed yes/no;
- synced permission count;
- archive destination basename/identifier as appropriate;
- exception class on failure.

Do not log secrets, full request/session payloads, or arbitrary manifest contents.

## 13. Tests

Create focused tests, proposed:

`tests/Feature/System/SystemModulesControlTest.php`

Coverage must include:

1. route `admin.system.modules` requires `system.modules.view`;
2. canonical Admin Menu entry uses `/admin/system/modules` and `system.modules.view`;
3. `ModulesForm` uses `AuthorizesSystemActions`;
4. every mutation action enforces `system.modules.update`;
5. read-only user can render page but cannot mutate;
6. required module cannot be disabled;
7. disabled dependency blocks enable;
8. enabled dependent blocks disable;
9. failed migration does not proceed to permission/manifest enable stage;
10. failed permission sync does not update manifest;
11. manifest write is outside Livewire and uses canonical module data;
12. archive retains existing required/enabled/dependent protections;
13. per-module lock prevents concurrent lifecycle operation;
14. raw exception messages are not flashed to browser;
15. route-title validation enforces max 255;
16. route title/menu mutations require update permission;
17. realtime mutation requires update permission;
18. `ModuleRouteManager::rows()` avoids the existing per-route AdminMenu N+1 if implementation changes it.

Tests should mock high-impact migration/archive behavior where possible; do not run destructive real migrations in focused unit/feature tests unless the repository already has an isolated test fixture designed for that purpose.

## 14. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/ModulesForm.php`
- `Modules/System/resources/views/livewire/settings/modules-form.blade.php`
- `Modules/System/Services/SystemModuleControlService.php` (new)
- `Modules/Admin/Services/ModuleRouteManager.php` (only for bounded N+1 improvement if verified)
- `Modules/Admin/data/menus.json` (normalize existing Modules menu entry)

Potentially reused unchanged:

- `App/Modules/ModuleLifecycleManager.php`
- `App/Modules/ModulePermissionManager.php`
- `App/Services/RealtimeManager.php`
- existing `/admin/system/modules` route/controller/page

Tests:

- `tests/Feature/System/SystemModulesControlTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-modules-form/ANALYSIS.md`
- `docs/modules/System/livewire/settings-modules-form/REFACTOR_PLAN.md`

No database schema migration is planned.
No new route is planned.
No duplicate Admin Menu entry is planned.

## 15. Existing Installation Menu Update

Because `AdminMenuSeeder` may not overwrite an existing populated menu table, post-deploy guidance will include a narrowly scoped idempotent update for the existing Modules entry:

- normalize URL to `/admin/system/modules`;
- set `can` to `system.modules.view`;
- preserve existing parent and active state where possible.

Do not wipe or reseed the full menu table.

## 16. Acceptance Criteria

- [ ] page remains at `/admin/system/modules`;
- [ ] route/menu visibility uses `system.modules.view`;
- [ ] existing Admin Menu entry is normalized, not duplicated;
- [ ] every mutation enforces `system.modules.update`;
- [ ] Livewire no longer writes module manifest files directly;
- [ ] lifecycle orchestration is moved into a service;
- [ ] module operations use per-module lock;
- [ ] dependency/required/archive protections remain intact;
- [ ] migration failure cannot mark module enabled;
- [ ] permission-sync failure cannot update manifest;
- [ ] browser errors are sanitized;
- [ ] route-title validation is explicit;
- [ ] route/menu/realtime mutations are authorized;
- [ ] repeated mutation UI actions are disabled while processing;
- [ ] focused tests pass;
- [ ] no destructive menu/database reset occurs.

## 17. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.

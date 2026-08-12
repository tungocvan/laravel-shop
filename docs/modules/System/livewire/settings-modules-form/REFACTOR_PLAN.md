# Settings/ModulesForm Livewire Refactor Plan

Plan date: 2026-08-12

Status: **Implemented 2026-08-12**

Scope: `Modules/System/Livewire/Settings/ModulesForm.php`, its direct services/UI/tests, and normalization of the existing Admin Menu entry for `/admin/system/modules`.

## Implemented outcome

The approved major refactor has been completed without adding a new route or duplicate menu entry.

### Authorization

- route/menu visibility remains `system.modules.view`;
- all mutation actions enforce `system.modules.update`:
  - `toggleRealtime()`
  - `toggleModule()`
  - `deleteModule()`
  - `saveRouteTitle()`
  - `addRouteToMenu()`.

### Lifecycle orchestration

Created:

`Modules/System/Services/SystemModuleControlService.php`

The service now owns canonical registry lookup, per-module locking, dependency preflight, migration ordering, permission synchronization, manifest enabled-state writes, runtime config updates, archive orchestration and safe structured logging.

Livewire no longer writes manifest files or orchestrates migration/permission/archive workflows directly.

### Concurrency

Module toggle/archive operations use a cache lock with key prefix:

`system:module-control:`

Concurrent mutation of the same module is rejected with a controlled retry-later message.

### Manifest / partial-state policy

Enable order is:

```text
preflight
→ migration
→ permission sync
→ manifest/runtime enable
```

Migration or permission failure therefore cannot mark the module enabled. The implementation does not claim atomic rollback for schema changes that already completed; detailed failures are logged server-side.

### Realtime / route/menu mutations

Realtime mutation delegates through the control service and is authorized at the Livewire boundary.

Route-title and add-menu actions remain delegated to `ModuleRouteManager` but now require `system.modules.update`. Route title is validated as required/string/max:255.

### Performance

`ModuleRouteManager::rows()` now preloads Admin Menu URLs once instead of querying menu existence once per route.

### UI

- enable/disable confirmation added;
- archive confirmation retained;
- loading/disabled states added;
- users without `system.modules.update` receive a read-only UI;
- realtime and route/menu controls respect read-only state;
- raw internal mutation exceptions are no longer displayed;
- database status failures are shown generically.

### Admin Menu

The existing entry was normalized, not duplicated:

```text
Name: Quản lý Modules
URL: /admin/system/modules
Can: system.modules.view
Active: true
```

Existing databases may still need the targeted post-deploy update described in deployment guidance because populated menu tables are not automatically overwritten by the menu seeder.

### Tests

Added:

`tests/Feature/System/SystemModulesControlTest.php`

The focused suite covers route/menu permission alignment, mutation authorization contract, required/dependency rules, lifecycle stage ordering, manifest behavior on failures, lock presence, route-title validation, N+1 regression protection and sanitized error handling.

## Acceptance checklist

- [x] page remains at `/admin/system/modules`;
- [x] route/menu visibility uses `system.modules.view`;
- [x] existing Admin Menu entry normalized, not duplicated;
- [x] every mutation enforces `system.modules.update`;
- [x] Livewire no longer writes module manifests directly;
- [x] lifecycle orchestration moved into a service;
- [x] module operations use per-module lock;
- [x] dependency/required/archive protections remain intact;
- [x] migration failure cannot mark module enabled;
- [x] permission-sync failure cannot update manifest;
- [x] browser errors are sanitized;
- [x] route-title validation is explicit;
- [x] route/menu/realtime mutations are authorized;
- [x] repeated mutation UI actions are disabled while processing;
- [x] no destructive menu/database reset introduced;
- [ ] focused tests must be executed in the user's PHP runtime after pull/merge.

## Verification command

```bash
php artisan test tests/Feature/System/SystemModulesControlTest.php
```

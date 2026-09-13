# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Header Authorization + Menu Drag & Drop Recovery**

Status: **IMPLEMENTATION COMPLETE — FOCUSED TESTS PASS / UI PASS / PR-MERGE GATE READY**

Branch: `fix/admin-header-authorization-403`

## Scope completed

### Header authorization

- `/admin/layout/header` now uses the Header-specific view permission `admin.header.view` instead of inheriting the generic `admin.layout.view` permission.
- Header save/reset operations use `admin.header.update`.
- Other layout sections continue to use `admin.layout.view` / `admin.layout.update`.
- Livewire authorization no longer depends on a non-existent custom `hasPermission()` method; authorization is evaluated through Laravel/Spatie-compatible permission checks.
- No hardcoded Super Admin role bypass was introduced.

### Menu drag & drop

- SortableJS is declared as an application dependency and loaded through the existing Vite entrypoint.
- The Admin menu tree continues to use the existing nested Sortable integration and `updateMenuOrder()` backend contract.
- Browser/runtime verification confirmed SortableJS is loaded and attached to `#root-menu-list`.
- Drag interaction is configured to use SortableJS fallback mode (`forceFallback`) to avoid the browser-native drag behavior that prevented the menu handle from moving in the target environment.
- Existing nested menu semantics and `admin.menu.update` authorization remain unchanged.

## Important commits

- `949d06bf` — `fix(admin): isolate header route permission`
- `3bd2d004` — `test(admin): align route authorization contracts`
- `153bc90d` — `fix(admin): load SortableJS for menu drag and drop`
- `80db2531` — `fix(admin): declare SortableJS menu dependency`
- `cffa9cbd` — `fix(admin): harden menu drag fallback`
- `package-lock.json` generated dependency lock is committed and the working tree is clean/synced with origin.

## Verification completed

User-reported focused test run:

```bash
php artisan test \
tests/Feature/Admin/AdminHeaderConfigurationContractTest.php \
tests/Feature/Admin/AdminRouteConfigurationTest.php \
tests/Feature/Admin/AdminHeaderSettingsUiContractTest.php
```

Result: **27 passed / 164 assertions**.

Manual runtime verification reported by the user:

- `/admin/layout/header`: **UI PASS**.
- Header title save/reset flow: **PASS**.
- `/admin/menus`: drag & drop from the six-dot handle: **UI PASS** after fallback-mode correction.

Working tree after verification:

```text
## fix/admin-header-authorization-403...origin/fix/admin-header-authorization-403
```

No full-project regression was requested or required for this module-scoped fix.

## Safety / compatibility notes

- Header permission isolation preserves least-privilege behavior.
- No schema migration was introduced.
- No role-name bypass was added.
- Existing menu persistence remains owned by `MenuTable::updateMenuOrder()` / `MenuService::updateOrder()`.
- Existing menu snapshot, import/export and Google Drive snapshot behavior is outside the functional scope of this fix and should remain unchanged.

## Prior accepted checkpoint

The previous Admin Menu Snapshot Library work remains accepted: named Google Drive menu snapshots, local sync, explicit restore, selected-only export/snapshot semantics and the System-owned Google Drive portable-file boundary were already verified and merged before this task.

## PR / merge gate

This branch is ready for PR creation against `main` after this handoff closeout commit is included. Merge only after confirming the PR head contains the handoff update and the focused verification evidence above.

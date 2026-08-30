# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Ownership & Reachability Baseline + P0 Database Isolation + Canonical Shell Guardrails**

Status: **IMPLEMENTED — awaiting local focused verification**

Branch/checkpoint: `refactor/admin-ownership-reachability-baseline`

This slice was explicitly approved after `/analyze Admin` concluded **Major Refactor**.

## Canonical Admin Shell

Preserve these Admin-owned surfaces:

- dashboard entry/composition;
- `/admin/menus`, `/admin/menus/create`, `/admin/menus/{id}/edit` as Admin sidebar/navigation configuration;
- `AdminMenu`, `MenuService`, `MenuImportExportService` for Admin navigation metadata;
- Admin sidebar rendering/navigation composition;
- profile/preferences shell UI;
- layout/header/sidebar/footer/design/theme/navigation workspaces and shell services.

Important ownership rule: a sidebar menu item that links to Product, Order, System, Account, or another module is navigation metadata. The target module remains the canonical owner of its business routes, models, services, permissions, and workflows.

## Changes in this slice

### Ownership baseline

Added `docs/modules/Admin/OWNERSHIP_BASELINE.md` defining:

- `KEEP`, `MOVE`, `DEPRECATE`, `DEAD`, `UNKNOWN`, and `QUARANTINE` states;
- canonical Admin shell whitelist;
- explicit canonical ownership of `/admin/menus` for sidebar/navigation setup;
- legacy family ownership candidates without authorizing bulk moves/deletions;
- caller/reachability evidence required before file removal or migration;
- schema/migration unknowns that remain blockers.

Updated `docs/modules/Admin/README.md` to reference the baseline and make sidebar/menu ownership explicit.

### Canonical shell guardrail tests

Added `tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php` to protect:

- Admin manifest type `shell`;
- declared Auth/User/Role dependencies;
- active route imports limited to `AdminController`, `DashboardController`, `MenuController`, and `ProfileController`;
- `/admin/menus` route group and `admin.menu.*` capability contract;
- intentionally closed Admin API route file.

### P0 database isolation tests

Added `tests/Feature/Admin/AdminDatabaseIsolationContractTest.php` to protect:

- absence of database administration from active Admin web/API routes;
- empty Admin API surface;
- fail-closed `Modules/Admin/Livewire/Database/TableList.php` behavior;
- all exposed legacy database actions delegating to the deny boundary;
- HTTP 403 containment;
- no `DatabaseService` reference from the legacy database Livewire component.

No database operation was re-enabled. `Modules/Admin/Services/DatabaseService.php` remains quarantined and is not considered production-safe.

## Runtime / behavior impact

Application implementation behavior change: **NONE EXPECTED**

Route name change: **NONE**

Permission name change: **NONE**

Database/schema/migration change: **NONE**

Admin UI change: **NONE**

Legacy domain migration/removal: **NONE**

This slice adds documentation and architecture/security contract tests only.

## Required local verification

Run only focused Admin tests for this slice:

```bash
php artisan test \
  tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php \
  tests/Feature/Admin/AdminDatabaseIsolationContractTest.php \
  tests/Feature/Admin/AdminLayoutContractTest.php
```

Then run the existing focused Admin regression suite only if the new contracts pass:

```bash
php artisan test tests/Feature/Admin
```

Do not run the full project test suite for this checkpoint.

Manual UI smoke is optional for this slice because no runtime/UI source changed. If performed, confirm `/admin`, `/admin/menus`, and `/admin/layout` remain unchanged.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` is still dangerous if made reachable. It remains source-level legacy code and must stay quarantined until a separately approved hardened System/database-operation design exists.

### P1

Admin still physically contains legacy domain/system code. This slice deliberately does not delete or move it because exact caller reachability and production schema usage are not fully proven.

Production migration-ledger/table ownership state remains unresolved.

## Acceptance criteria for this checkpoint

Before PR readiness:

- new ownership boundary tests PASS;
- new database isolation tests PASS;
- existing Admin layout contract test PASS;
- focused Admin regression PASS if run;
- working tree clean after syncing the branch;
- no runtime route/permission/schema/UI behavior drift is observed.

## Next phase

Next domain migration/refactor slice: **NOT AUTHORIZED YET**.

After this checkpoint is verified and merged, inspect canonical module coverage and propose one legacy Admin family for migration. Do not bulk-delete legacy code and do not move schema/migrations without production-ledger verification.

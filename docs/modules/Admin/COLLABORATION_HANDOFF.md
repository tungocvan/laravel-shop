# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Database P0 Containment & Reachability Cleanup**

Status: **VERIFIED — PR READY**

Branch/checkpoint: `refactor/admin-database-p0-containment`

This approved slice contains the legacy Admin database-administration capability without redesigning or deleting database operations. Canonical database administration remains owned by `Modules/System`; the historical Admin database service and Livewire surfaces remain quarantined compatibility debt until stronger external/dynamic caller proof authorizes deletion.

## Ownership decision

Responsibilities are intentionally split:

- `Modules/System` is the canonical owner of database-administration routes, controller and operational service;
- `Modules/Admin` remains the authenticated shell only and must not expose an independent destructive database runtime;
- legacy Admin database Livewire surfaces are fail-closed;
- `Modules/Admin/Services/DatabaseService.php` remains P0 quarantine and was not redesigned, moved or deleted.

## Runtime containment changes

The legacy Admin database Livewire family is now consistently fail-closed:

- `Modules/Admin/Livewire/Database/TableList.php` was already fail-closed and remains unchanged;
- `Modules/Admin/Livewire/Database/BackupManager.php` no longer resolves `Modules\Admin\Services\DatabaseService`, does not enumerate operational backup data, and rejects restore/destructive actions with HTTP 403;
- `Modules/Admin/Livewire/Database/ImportDrawer.php` no longer resolves the Admin DatabaseService or accepts/imports SQL and rejects the import action with HTTP 403.

No destructive capability was moved into another Admin component. The legacy Blade views remain compatibility surfaces and are not proof of canonical ownership.

## Canonical System boundary verified

Runtime route verification confirms the active database-administration URLs are System-owned:

```text
GET|HEAD admin/system/database
GET|HEAD admin/system/database/backup-restore
GET|HEAD admin/system/database/download/{filename}
```

They are declared in `Modules/System/routes/web.php` under `web` + `auth:admin`, with dedicated `database.view` / `database.download` permission middleware. `Modules/System/Http/Controllers/DatabaseController.php` resolves `Modules\System\Services\DatabaseService`, not the quarantined Admin service.

The other import/export/download routes returned by broad route grep belong to Admission, Muasamcong, Order, Request or ClientPortal and are unrelated to the legacy Admin database capability.

## Reachability decision

Repository caller searches found no canonical Admin route or ordinary static reference to the legacy Admin DatabaseService, BackupManager or ImportDrawer. This is strong containment evidence but not complete proof against dynamic/external Livewire callers.

Therefore this slice deliberately does **not** delete:

- `Modules/Admin/Services/DatabaseService.php`;
- `Modules/Admin/Livewire/Database/TableList.php`;
- `Modules/Admin/Livewire/Database/BackupManager.php`;
- `Modules/Admin/Livewire/Database/ImportDrawer.php`;
- their legacy database Blade views.

They remain `QUARANTINE / FAIL-CLOSED` compatibility debt. Deletion requires a separately proven caller/removal slice.

## Explicitly out of scope

- redesign of System database administration;
- schema, migration or production-data changes;
- deletion or movement of the Admin DatabaseService;
- Affiliate commission/rank/scheme ownership;
- environment/settings compatibility cleanup;
- Banner/Header/Flash Sale compatibility-adapter deletion;
- unrelated import/export features in other modules.

## Verification completed

```text
AdminDatabaseP0ContainmentContractTest + AdminOwnershipBoundaryContractTest: 9 passed, 48 assertions
Admin canonical database/destructive route exposure: PASS — no Admin-owned database operation route
System database route ownership: PASS — /admin/system/database* remains System-owned
Working tree after focused verification: clean
```

The containment contract protects the legacy Admin database boundary from resolving the Admin DatabaseService or exposing destructive entry points. No full-project regression is required for this narrowly scoped containment slice.

## Acceptance criteria

- canonical database-administration owner: **System — VERIFIED**;
- Admin independent database route ownership: **NONE — VERIFIED**;
- legacy Admin TableList destructive actions: **FAIL-CLOSED**;
- legacy Admin BackupManager operational/destructive actions: **FAIL-CLOSED**;
- legacy Admin ImportDrawer import action: **FAIL-CLOSED**;
- legacy Admin DatabaseService reachable from canonical Admin database runtime: **NO STATIC/CANONICAL CALLER FOUND**;
- complete external/dynamic zero-caller proof: **NOT CLAIMED**;
- legacy Admin database files deleted: **NO**;
- System database runtime redesigned: **NO**;
- schema/migration/data changes: **NONE**;
- focused Admin regression: **PASS — 9 tests / 48 assertions**;
- P0 containment: **VERIFIED**;
- PR readiness: **READY**.

## Material risks still open

### P0 compatibility debt

`Modules/Admin/Services/DatabaseService.php` still contains destructive historical capability. Its safety contract is containment: it must stay unreachable from canonical Admin runtime. Do not reactivate or delete it without a separately approved caller-proof/redesign scope.

### Dynamic/external callers

Repository/static searches are insufficient to prove every historical Livewire alias or external integration absent. This is why the fail-closed compatibility surfaces remain instead of being deleted in this slice.

### Remaining Admin legacy families

Affiliate commission/rank/scheme ownership remains the next major architectural debt candidate because current behavior spans Website, Order, Product and shared User concerns.

Environment/System compatibility adapters and the deprecated Banner/Header/Flash Sale adapters remain separate caller-proof cleanup debt.

## Next phase

Database P0 containment is closed out and PR-ready. Do not implement Affiliate or another Admin legacy family until this branch is merged and the user explicitly authorizes the next scope.

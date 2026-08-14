# Modules/Role - Refactor Plan

Updated: 2026-08-14

Status: **Approved and implemented on `agent/refactor-role`; pending local PHP/Pint/PHPUnit verification before merge.**

## Goal

Harden `Modules/Role` without rebuilding its working route/controller/Livewire/UI/import-export foundations.

## Approved Scope

- capability authorization on Role routes and Livewire mutations
- protected `Super Admin` invariant
- canonical transactional `RoleService`
- server-owned permission catalog enforcement
- admin-guard-only Role import
- prevention of arbitrary permission creation from browser/upload input
- focused Role route authorization test
- documentation refresh

## Implemented Changes

### P0 Authorization

- `admin.role.index` requires `permission:view_role,admin`.
- `admin.role.create` requires `permission:create_role,admin`.
- `admin.role.edit` requires `permission:edit_role,admin`.
- `RoleForm` re-authorizes create/edit in `mount()` and `save()`.
- `RoleTable` requires `view_role` to mount/render and `delete_role` for delete/bulk-delete.
- Permission-catalog mutation is restricted to actors with the `Super Admin` role.

### P0 Protected Role

- `Super Admin` cannot be opened for normal RoleForm editing.
- `RoleService::save()` rejects mutation of `Super Admin`.
- single and bulk delete keep `Super Admin` protected.

### P1 Service / Transactions

New file:

`Modules/Role/Services/RoleService.php`

Responsibilities:

- admin-guard role query/resolution
- active permission catalog resolution
- transactional create/update + permission sync
- protected-role invariant
- transactional single/bulk delete
- permission cache invalidation after successful writes

### P1 Permission Catalog

- Role save accepts only active server-owned permissions plus historical permissions already attached to the edited role.
- Submitted permissions must also exist in the admin `permissions` table.
- Permission synchronization UI can only create missing permissions that are explicitly declared by the active module catalog and only for `Super Admin`.

### P1 Import Hardening

`Modules/Role/Services/ImportExport.php` now:

- accepts only `guard_name = admin`
- rejects permission names outside active module manifests
- rejects declared permissions that have not yet been synchronized into the `permissions` table
- no longer calls `Permission::firstOrCreate()` from imported permission values
- keeps existing transaction, dry-run, duplicate modes and safe error handling

## Compatibility Preserved

- route names and canonical URLs
- legacy `/admin/role...` redirects
- Livewire aliases `role.role-table` and `role.role-form`
- existing permission names
- Spatie tables and relationships
- Shared Import/Export foundation
- no schema migration

## Database Impact

None. Historical migrations were not rewritten.

## Tests Added

- `tests/Feature/Role/RoleRouteAuthorizationTest.php`

This verifies route URI, `auth:admin`, capability middleware and numeric edit IDs.

## Local Verification Required

Run on the user's local VPS before merge:

```bash
php artisan test tests/Feature/Role/RoleRouteAuthorizationTest.php
./vendor/bin/pint Modules/Role tests/Feature/Role
php artisan test
```

Manual checks:

1. Super Admin login still succeeds.
2. Admin without Role capabilities receives 403 on protected pages/actions.
3. Authorized role create/edit works.
4. Clearing all normal permissions persists correctly.
5. `Super Admin` cannot be edited/deleted.
6. Role in use cannot be deleted.
7. Import rejects non-admin guard.
8. Import rejects unknown permission names.

## Rollback

All implementation changes are source-only. No schema/data migration was introduced, so rollback is a normal git revert of the Role refactor commits.

## Deferred / Non-Goals

- `shell` -> `support` manifest decision
- negative-year migration filename cleanup
- `module_migrations` ownership
- placeholder `/api/role` removal
- broad UI redesign
- immutable protected-role database key

## Merge Gate

Do not merge to `main` until targeted Role verification and the project regression suite pass locally.

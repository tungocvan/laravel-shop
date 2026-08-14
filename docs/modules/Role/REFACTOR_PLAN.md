# Modules/Role - Refactor Plan

Updated: 2026-08-14

Status: **Awaiting explicit user approval before application code changes.**

Sources:

- current source under `Modules/Role/**`
- `docs/modules/Role/ANALYSIS.md`
- `docs/modules/Role/INFORMATION.md`
- `docs/modules/Role/README.md`
- `.codex/bootstrap/*`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/prompts/import-export.md`
- `ROADMAP.md`

## 1. Refactor Goal

Harden `Modules/Role` as the canonical administrator authorization-management module without rebuilding its working route/controller/Livewire/UI/import-export foundations.

Primary goals:

1. Close capability-level authorization gaps.
2. Protect the `Super Admin` security invariant across every write path.
3. Move sensitive Role mutation workflows out of Livewire into a canonical service.
4. Restrict permission creation/import to a server-approved permission catalog.
5. Make multi-record/multi-step writes transactional.
6. Add targeted security and regression coverage before broad cleanup.
7. Preserve public contracts unless explicitly documented otherwise.

Final architectural direction: **Major Refactor, not Full Rebuild.**

## 2. Scope

### In scope

- `Modules/Role/config/module.php`
- `Modules/Role/routes/web.php`
- `Modules/Role/Http/Controllers/RoleController.php`
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/resources/views/pages/roles/**`
- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/ImportExport.php`
- new Role service/policy classes when justified by implementation
- focused Role tests
- Role documentation updates after implementation

Direct dependencies may be inspected or minimally changed only when required by the approved Role refactor contract.

### Explicit non-goals

- No full rebuild of Role.
- No global permission-system rewrite.
- No migration-history rewrite in this refactor slice.
- No unrelated Admin/System/User refactor.
- No replacement of Spatie Permission.
- No new module-registration mechanism.
- No new CSS/UI framework.
- No broad Shared Import/Export redesign unless a concrete Role defect cannot be fixed locally.
- No removal/renaming of existing route names or Livewire aliases in the first implementation slice.

## 3. Compatibility Contracts To Preserve

Unless later approved separately, implementation must preserve:

- canonical routes:
  - `GET /admin/roles` -> `admin.role.index`
  - `GET /admin/roles/create` -> `admin.role.create`
  - `GET /admin/roles/{id}/edit` -> `admin.role.edit`
- legacy `/admin/role...` redirects
- Livewire aliases:
  - `role.role-table`
  - `role.role-form`
- Spatie tables and relationships
- existing permission names:
  - `view_role`
  - `create_role`
  - `edit_role`
  - `delete_role`
  - `import_role`
  - `export_role`
- Shared Import/Export foundation
- existing FastExcel import/export file contract where safe
- Admin layout `Admin::layouts.master`

## 4. Findings Being Addressed

### P0-01 — Missing capability authorization

Evidence: Role web routes currently require only `auth:admin`; Livewire mutation methods do not independently authorize named Role capabilities.

Impact: an authenticated admin can potentially change authorization configuration without the intended capability.

### P0-02 — `Super Admin` is mutable through RoleForm

Evidence: RoleForm can load any role ID and save its name/permissions. Global `Gate::before` depends on exact role name `Super Admin`.

Impact: rename/mutation can break the global administrative bypass invariant and weaken recovery access.

### P1-01 — Sensitive Role workflows live in Livewire

Evidence: create/update/sync/delete/bulk-delete/permission-generation logic remains in `RoleForm` and `RoleTable`.

Impact: inconsistent transaction, authorization and invariant enforcement across callers.

### P1-02 — Permission catalog can be expanded from browser/import input

Evidence:

- `RoleTable::createModulePermissions()` can create permission names from Livewire state.
- `ImportExport::permissionNamesForSync()` uses `Permission::firstOrCreate()` for imported names.

Impact: permission records can drift outside active module manifests/server-owned capability catalog.

### P1-03 — Transaction boundaries incomplete

Evidence:

- role save + permission synchronization is not wrapped in one explicit transaction.
- bulk role deletion is not atomic.

### P1-04 — Guard and validation hardening needed

Evidence:

- RoleForm persists `guard_name = admin`, but name uniqueness is not explicitly guard-scoped.
- import accepts arbitrary non-empty guard strings.

### P1-05 — Test coverage not proven

Security-sensitive denied/allowed behavior needs focused tests before merge.

### Deferred findings

The following are intentionally deferred from the first refactor slice:

- malformed negative-year migration filenames
- `module_migrations` ownership
- public placeholder API cleanup
- manifest `shell` vs bootstrap `support` architecture drift
- broad UI polish

These are real findings but are not required to close the immediate privilege-escalation boundary.

## 5. Target Architecture

Preferred flow after refactor:

```text
Route permission boundary
-> thin RoleController
-> Page Blade
-> RoleForm / RoleTable Livewire UI state
-> RoleService
   -> protected-role invariant
   -> validated permission catalog
   -> DB transaction
   -> Spatie Role / Permission
   -> permission cache invalidation

Shared Import/Export Panel / caller
-> Modules/Role/Services/ImportExport
   -> server-approved Role/permission rules
   -> Shared BaseImportExportService
   -> Spatie Role / Permission
```

Authorization must exist at sensitive caller boundaries even when the service also enforces domain invariants.

## 6. Implementation Phases

### Phase R0 — Security regression baseline

Create focused tests that define the required behavior before or alongside implementation:

- unauthenticated Role route denied
- admin without `view_role` denied list
- admin without `create_role` denied create route/action
- admin without `edit_role` denied edit/save
- admin without `delete_role` denied delete/bulk delete
- direct Livewire mutation requests denied when capability is missing
- permitted admin succeeds
- Super Admin mutation/deletion cases covered

Acceptance gate: denied behavior must be explicit and fail closed.

### Phase R1 — Route/page capability authorization

Apply capability-specific authorization while preserving route names and URLs.

Expected mapping:

- index -> `view_role`
- create page -> `create_role`
- edit page -> `edit_role`

Rules:

- keep `auth:admin`
- add named permission middleware or equivalent repository-standard boundary
- legacy redirects remain authenticated and do not create a bypass
- controller remains thin

### Phase R2 — Livewire mutation authorization

Authorize each sensitive public Livewire method independently.

RoleForm:

- create save -> `create_role`
- edit save -> `edit_role`

RoleTable:

- delete/deleteSelected -> `delete_role`
- permission-catalog mutation -> must require an explicit high-trust capability; until a dedicated capability is approved, restrict the action to `Super Admin` rather than reusing a lower-trust CRUD permission

UI `@can`/visibility changes are secondary UX only; server-side denial remains mandatory.

### Phase R3 — Protected `Super Admin` invariant

For the first safe slice, use a strict server-owned invariant:

- existing role named exactly `Super Admin` cannot be renamed through Role management
- cannot be deleted singly or in bulk
- ordinary RoleForm editing must not reduce/change its role identity
- import cannot create/update/replace `Super Admin` unless the existing service's explicit Super Admin actor rule is further constrained by the approved catalog checks

Important: this phase does **not** introduce a database schema change for immutable role keys. A stable-key redesign can be planned separately if needed.

### Phase R4 — Introduce `RoleService`

Create `Modules/Role/Services/RoleService.php` as the canonical mutation/query workflow boundary.

Responsibilities should include only what current source needs:

- resolve admin-guard roles
- paginated/search query builder or query method
- create/update role + permission synchronization
- validate/enforce protected-role invariant
- delete one role
- bulk delete eligible roles
- transaction boundaries
- Spatie permission cache invalidation where required

Livewire remains responsible for UI state, validation messages, pagination state and notifications.

Do not over-engineer DTO/event/audit infrastructure in this slice unless tests demonstrate a concrete need.

### Phase R5 — Permission catalog hardening

Use `App\Modules\ModulePermissionManager` active groups as the server-approved catalog for normal Role assignment.

Rules:

- submitted RoleForm permission names must be a subset of approved active admin permissions plus explicitly preserved historical permissions already attached to the edited role
- browser input must not create new `Permission` rows
- permission generation cannot manufacture arbitrary undeclared capabilities

Recommended first-slice behavior for `createModulePermissions()`:

- disable/reject arbitrary permission creation from free-form module input unless the requested names exist in the server-owned active module catalog
- if no legitimate current business requirement remains for free-form permission generation, keep UI compatibility only long enough to remove/disable the unsafe action deliberately

No new `manage_permission_catalog` permission will be silently introduced in this refactor. Adding a new public permission contract requires explicit approval because it affects seeders/menus/operator roles.

### Phase R6 — Import hardening

Keep `Modules/Role/Services/ImportExport.php` and Shared foundation.

Changes:

- accept only `guard_name = admin`
- do not use imported names to create arbitrary `Permission` records
- resolve imported permissions against the approved server catalog
- reject unknown permission names with structured row errors
- preserve transaction, dry-run and safe error handling
- preserve `update_or_create`, `create_only`, and `skip_duplicate` behaviors currently supported
- continue rejecting `replace`
- maintain protected `Super Admin` handling with stricter invariant tests

No second import/export engine will be created.

### Phase R7 — Transaction/data-integrity hardening

- role create/update + permission sync in one transaction
- bulk deletion in one transaction where the intended operation is atomic
- protect roles with assigned users
- invalidate Spatie permission cache after successful relevant writes
- do not expose raw exception messages to UI

### Phase R8 — UI authorization and usability polish

After server boundaries are correct:

- hide/disable create/edit/delete controls when capability is absent
- present protected `Super Admin` state clearly
- maintain loading-disabled save/delete behavior
- preserve responsive Role table/form layout

Do not perform a broad visual redesign in this refactor.

### Phase R9 — Documentation and verification

After code implementation:

- update `ANALYSIS.md` resolved/open findings
- update `INFORMATION.md`
- update `README.md`
- mark this plan's implementation status
- run targeted Role tests
- run relevant project regression tests locally
- run Laravel Pint on changed PHP files

## 7. Files Expected To Change

Likely application files:

- `Modules/Role/routes/web.php`
- `Modules/Role/Http/Controllers/RoleController.php` only if route/controller authorization or typing requires it
- `Modules/Role/Livewire/RoleForm.php`
- `Modules/Role/Livewire/RoleTable.php`
- `Modules/Role/Services/ImportExport.php`
- `Modules/Role/resources/views/livewire/role-form.blade.php`
- `Modules/Role/resources/views/livewire/role-table.blade.php`
- `Modules/Role/Services/RoleService.php` (new)

Possible config change:

- `Modules/Role/config/module.php` only if an already-declared dependency/capability correction is required and compatibility is proven

Expected tests:

- `tests/Feature/Role/RoleRouteAuthorizationTest.php`
- `tests/Feature/Role/RoleLivewireAuthorizationTest.php`
- `tests/Feature/Role/ProtectedRoleTest.php`
- `tests/Feature/Role/RoleImportSecurityTest.php`
- focused service test(s) if repository test conventions support them

Documentation:

- `docs/modules/Role/ANALYSIS.md`
- `docs/modules/Role/INFORMATION.md`
- `docs/modules/Role/README.md`
- `docs/modules/Role/REFACTOR_PLAN.md`

## 8. Database / Migration Impact

First approved implementation slice: **no schema migration planned**.

Do not rename historical `-0001_...` migrations during this refactor.

Do not move/drop `module_migrations` during this refactor.

Reason: authorization containment can be completed safely without mixing migration-history risk into the same change set.

## 9. Security Impact

Expected improvement:

- Role management becomes fail-closed by named capability.
- direct Livewire mutation cannot bypass route/button authorization.
- `Super Admin` cannot be renamed/deleted via normal Role management.
- browser/import data cannot manufacture arbitrary permissions.
- import is constrained to the admin guard.

Security is the release gate for this refactor.

## 10. Transaction / Data-Integrity Impact

Expected changes:

- atomic role save + permission sync
- atomic eligible bulk delete
- consistent protected-role and in-use-role rules
- no permission creation from untrusted assignment/import input
- cache invalidation after committed permission changes

## 11. Performance Impact

No large performance refactor is planned.

Keep:

- pagination
- `withCount('users')`
- bounded page selection behavior

Import/export role cardinality is expected to be small, but import validation must occur before unsafe persistence. Export `get()` optimization is deferred unless tests/profile show material need.

## 12. Test Strategy

Minimum acceptance suite:

1. Route authorization matrix for view/create/edit.
2. Direct Livewire action authorization matrix for save/delete/bulk-delete/catalog mutation.
3. Super Admin rename/delete protection.
4. Normal admin role create/edit permission sync.
5. Empty permission selection sync behavior.
6. Role-in-use deletion remains blocked.
7. Bulk deletion behavior and rollback expectations.
8. RoleForm permission tampering rejected.
9. Import unknown permission rejected.
10. Import non-admin guard rejected.
11. Import protected-role behavior.
12. Existing legacy route redirects still work.
13. Existing Role aliases render.

Local verification after implementation should include targeted Role tests first, then relevant full regression.

## 13. Acceptance Criteria

Refactor is complete only when:

- unauthorized admins receive 403 for protected Role pages/actions
- authorized actors retain current intended Role workflows
- direct Livewire calls cannot bypass authorization
- `Super Admin` cannot be renamed or deleted through Role module normal workflows
- normal role create/update is transactional
- arbitrary browser/import permission names do not create new capabilities
- Role import accepts only admin guard and server-approved permissions
- roles assigned to users remain protected from deletion
- route names, canonical URLs, legacy redirects and Livewire aliases remain compatible
- targeted tests pass
- application docs reflect implemented reality
- no unrelated module behavior is changed

## 14. Rollback / Recovery

Implementation should be committed in reviewable steps.

Rollback strategy:

- authorization/service/UI changes are source-only and can be reverted without schema rollback
- no destructive data migration is included
- no permission names are removed in the first slice
- import behavior becomes stricter; if rollback is required, reverting source restores previous behavior without transforming stored Role data

Before merge, ensure at least one existing `Super Admin` account can still authenticate and retains the expected global Gate bypass.

## 15. Deferred Follow-Up Plan

After this security refactor is stable, separately consider:

1. change Role manifest from `shell` to `support` and declare the correct dependency graph
2. migration filename hygiene/fresh-install strategy
3. reassignment of `module_migrations` ownership
4. removal of placeholder `/api/role`
5. broader Role admin UI permission search/group UX
6. immutable protected-role key/schema if name-based identity remains an operational concern

These must not block the immediate P0 containment work.

## Approval Gate

**No application source code may be modified until the user explicitly approves this refreshed plan or explicitly asks to implement it.**

# Modules/Role - Analysis

Updated: 2026-08-14

Scope: documentation-only re-analysis of `Modules/Role` against the current `main` source and current `.codex` standards.

## Executive Summary

`Modules/Role` manages administrator roles and permissions using `spatie/laravel-permission`. The current implementation is materially better than the older Role analysis: import/export has been moved into a dedicated `Modules\Role\Services\ImportExport` service built on the shared import/export foundation, selection reset hooks exist in `RoleTable`, admin-guard permissions are filtered in `RoleForm`, and preserved inactive permissions are retained during edits.

However, the module still has a critical authorization boundary problem: all management web routes require only `auth:admin`, while sensitive Livewire mutation methods (`save`, `delete`, `deleteSelected`, and `createModulePermissions`) perform no capability-specific authorization. Any authenticated admin who can reach or invoke these Livewire components can potentially change authorization configuration.

Final recommendation: **Major Refactor**. A full rebuild is not justified because the route/controller/view structure, Spatie integration, shared import/export integration, pagination and UI foundations are reusable. The priority is to harden authorization and move remaining mutation/query workflows out of Livewire.

## Module Purpose and Overview

Primary responsibilities:

- List/search/paginate administrator roles.
- Create and edit roles.
- Assign permissions to roles.
- Delete unused non-Super-Admin roles.
- Generate conventional permission records for a named module/action set.
- Import/export role configuration through the repository shared import/export foundation.

Current manifest: `Modules/Role/config/module.php`.

Declared permissions:

- `view_role`
- `create_role`
- `edit_role`
- `delete_role`
- `import_role`
- `export_role`

The manifest currently declares `type => shell` and `depends => ['User']`.

## Bootstrap / Standards Context

Project stack relevant to Role:

- Laravel 12 / PHP 8.3.
- Livewire 3.6 runtime.
- Spatie Permission 6.23.
- First-party module discovery through `Modules\ModuleServiceProvider`.
- Shared import/export infrastructure under `Modules/Shared/Services/ImportExport`.

Repository bootstrap describes `Role` conceptually as a support/access module, but the current Role manifest explicitly declares `shell`. Because source/configuration has highest priority, runtime currently treats Role as shell. This mismatch should be resolved deliberately; it is documentation/architecture drift, not a reason to change runtime during analysis.

## Dependency Graph

```text
Modules/ModuleServiceProvider
  -> Modules/Role/config/module.php
  -> Modules/Role/routes/web.php
     -> RoleController
        -> page Blade
           -> role.role-table / role.role-form
              -> Spatie Role / Permission models
              -> PermissionRegistrar
              -> ModulePermissionManager (RoleForm)
  -> Modules/Role/routes/api.php
     -> API RoleController placeholder

Role Import/Export
  -> Modules/Role/Services/ImportExport
     -> Modules/Shared/Services/ImportExport/BaseImportExportService
     -> Spatie Role / Permission
     -> App/Models/User for current actor validation
```

Direct cross-module dependencies observed:

- `User` declared by manifest.
- `Shared` import/export service is used but not declared in `depends`.
- `App\Modules\ModulePermissionManager` supplies the active permission catalog.
- `App\Models\User` is used by import protection logic.

No circular dependency was proven in this analysis.

## Route / Controller / Blade / Livewire Analysis

### Web routes

File: `Modules/Role/routes/web.php`.

Current canonical routes:

- `GET /admin/roles` -> `admin.role.index`
- `GET /admin/roles/create` -> `admin.role.create`
- `GET /admin/roles/{id}/edit` -> `admin.role.edit`

Legacy `/admin/role...` URLs redirect to the plural routes.

All routes use `web` + `auth:admin` only.

**Finding P0 — missing capability authorization**

Priority: P0  
File: `Modules/Role/routes/web.php`, `Modules/Role/Livewire/RoleForm.php`, `Modules/Role/Livewire/RoleTable.php`  
Evidence: routes have only `auth:admin`; public mutation methods contain no `authorize`, `can`, policy, or permission check.  
Problem: authenticated administrators are not separated by `view_role/create_role/edit_role/delete_role`.  
Impact: role/permission configuration can be changed by an admin account that should not possess authorization-management capability. This is a privilege-escalation boundary.  
Recommendation: enforce named permissions at routes/pages and again at every sensitive Livewire mutation boundary. Treat UI visibility as secondary only.

### Controller

File: `Modules/Role/Http/Controllers/RoleController.php`.

The controller is thin and only renders pages, which matches the repository standard. Improvement opportunities are typing and route-model/ID validation, but these are secondary to authorization.

### API

`GET /api/role` is a public placeholder returning only a static success response. It does not expose role data, but it has no operational value.

Priority: P2  
File: `Modules/Role/routes/api.php`, `Modules/Role/Http/Controllers/Api/RoleController.php`  
Problem: dead/scaffold API surface.  
Recommendation: remove it after route compatibility is checked, or define an authenticated and authorized contract.

### RoleForm

File: `Modules/Role/Livewire/RoleForm.php`.

Positive changes versus the older analysis:

- Permission catalog comes from `ModulePermissionManager::activeGroups()`.
- Only `guard_name = admin` permissions are loaded.
- Permissions no longer active in the module catalog are preserved during edit instead of silently removed.
- `syncPermissions()` is always called, including when the selected active permission list is empty.

Remaining issues:

**P0 — mutation authorization absent.** `save()` can create/update a role and synchronize permissions without a capability check.

**P0 — protected Super Admin role is mutable.** Editing by ID loads and saves any role, including `Super Admin`; its name can be changed. The global `Gate::before` bypass depends on the exact role name `Super Admin`, so renaming it changes a core security invariant.

**P1 — role mutation workflow lives directly in Livewire.** `updateOrCreate()` and `syncPermissions()` should be owned by a Role service with an explicit transaction and protected-role invariant.

**P1 — submitted permission values are only validated as an array.** They should be validated against the server-approved admin permission catalog before synchronization.

**P1 — role uniqueness validation is not guard-aware.** Current validation uses `unique:roles,name,<id>` while persistence forces `guard_name = admin`. Use a guard-scoped uniqueness rule.

### RoleTable

File: `Modules/Role/Livewire/RoleTable.php`.

Positive changes versus the older analysis:

- `updatedSearch`, `updatedPerPage`, `updatedSelectAll`, and `resetSelection` now exist.
- Role listing is paginated.
- Single and bulk deletion block `Super Admin` and roles with assigned users.
- Missing role IDs are handled safely in single delete.
- Permission creation is transaction-wrapped and Spatie permission cache is cleared.

Remaining issues:

**P0 — delete and permission-generation actions lack authorization.** `delete`, `deleteSelected`, `openPermissionModal`, and `createModulePermissions` can be invoked by authenticated admins without `delete_role`/authorization-management capability checks.

**P1 — permission creation bypasses the canonical manifest catalog.** `createModulePermissions()` creates arbitrary conventional names from browser-provided module/action state. This can create permissions not declared by active module manifests and can drift from `ModulePermissionManager`.

**P1 — role deletion business rules are embedded in Livewire.** These rules should be centralized so UI, imports, CLI, and future API callers cannot bypass them.

**P1 — bulk deletion is not atomic.** It loops and deletes independently. Partial deletion is possible if a later delete fails.

**P1 — query ownership remains in Livewire.** `queryRoles()` is simple and paginated, but repository standards place reusable filtering/query workflows in services.

## Service Analysis

### ImportExport

File: `Modules/Role/Services/ImportExport.php`.

This is a substantial improvement and aligns with the canonical shared import/export architecture.

Strengths:

- Extends `Modules\Shared\Services\ImportExport\BaseImportExportService`.
- Performs capability authorization for import/export/template actions.
- Uses row normalization and Validator rules.
- Uses `(name, guard_name)` as duplicate identity.
- Rejects replace mode.
- Wraps non-dry-run imports in a transaction.
- Protects `Super Admin` import unless actor is Super Admin.
- Clears Spatie permission cache.
- Logs internal failures and returns a generic user-facing error.

Issues:

**P1 — import may create arbitrary permissions.** `permissionNamesForSync()` uses `Permission::firstOrCreate()` for every imported permission string. This can bypass the active permission catalog and manufacture new authorization capabilities from uploaded data.

**P1 — guard input remains importer-controlled.** Normalization defaults to `admin`, but any non-empty imported guard string passes current validation. Role administration is otherwise admin-guard oriented. An explicit allowlist should be considered.

**P1 — export loads the complete role set with `get()`.** Role counts are usually small, so current production risk is lower than large business tables, but the shared standard still prefers bounded/lazy export behavior when dataset growth is possible.

**P1 — dependency contract drift.** Service uses Shared directly while Role manifest declares only `User`.

## Import / Export Analysis

Import/export is present through the Role service and the Shared base service. No competing local JSON engine was observed in current `RoleTable`; the older documentation describing JSON import/export inside Livewire is stale.

Critical validation items for any refactor:

- Keep server-side capability checks in the service.
- Do not allow import to define previously unknown permission capabilities unless explicitly intended and restricted.
- Protect `Super Admin` and other future system roles as immutable domain invariants.
- Restrict accepted guards to the supported authorization model.
- Keep transaction rollback and safe error reporting.

## Shared Dependencies

Observed:

- `Modules/Shared/Services/ImportExport/BaseImportExportService`.
- `App/Modules/ModulePermissionManager`.
- Spatie `Role`, `Permission`, `PermissionRegistrar`.
- `App/Models/User`.

No module-local model class is required for roles; Spatie models are the persistence models.

## Model / Migration / Database Analysis

Role uses Spatie models directly rather than `Modules/Role/Models/*` for its active workflows.

Role migration directory contains:

- `-0001_11_30_000010_create_permissions_table.php`
- `-0001_11_30_000011_create_roles_table.php`
- `-0001_11_30_000012_create_model_has_permissions_table.php`
- `-0001_11_30_000013_create_model_has_roles_table.php`
- `-0001_11_30_000014_create_role_has_permissions_table.php`
- `2026_04_20_104916_module_migrations.php`

Primary authorization tables are the Spatie role/permission tables.

**P1 — malformed migration naming remains.** Negative-year filenames are non-standard and are explicitly called out by the repository roadmap migration-hygiene work.

**P1 — `module_migrations` ownership is questionable.** This table appears infrastructure-oriented rather than Role-domain data and should be reassessed under canonical ownership rules before changing it.

Migration history must not be rewritten casually; any correction needs a fresh-install/production compatibility plan.

## Security

Primary risk rating: **Critical until authorization boundaries are fixed**.

Security-sensitive invariants required:

- Only authorized actors can view/create/edit/delete roles.
- Only explicitly privileged actors can change the authorization catalog.
- `Super Admin` identity must not depend solely on a mutable UI-editable name.
- Browser or import input must not be able to manufacture arbitrary capabilities without an allowlisted server-side contract.
- Every Livewire mutation must authorize independently of route access and button visibility.

## Performance

Current role list is paginated and uses `withCount('users')`, which is appropriate.

Potential improvements:

- Move role query construction to a service for consistency/testability.
- Keep import/export bounded if role/permission cardinality grows.
- Avoid serializing full Eloquent Permission models in public Livewire state where a compact DTO/array is sufficient.

No major N+1 problem was proven in the inspected list/form PHP paths.

## Validation and Authorization

Validation exists for role name, permission array, module name and import rows. The key weakness is not absence of validation generally, but insufficient validation of authorization-domain values:

- role guard should be constrained,
- permission names should be checked against an approved catalog,
- protected role mutation should be blocked,
- capability authorization must be enforced on mutations.

## Transactions, Concurrency and Data Integrity

- Import uses a transaction: good.
- Permission generation uses a transaction: good.
- Role create/update + permission sync is not transaction-wrapped: P1.
- Bulk role deletion is not transaction-wrapped: P1.
- Spatie cache invalidation exists after permission generation and import. Role form mutation relies on Spatie behavior; explicit invalidation strategy should be verified during refactor tests.

## Admin UI / UX Standard Review

The Role form uses a clean two-column admin workspace, clear title/actions, inline validation, loading-disabled save state, responsive grids, and visually grouped permission cards. These align broadly with `ADMIN_UI_STANDARD.md`.

UI concerns:

- Root `max-w-7xl mx-auto` constrains the page inside an admin shell that now prefers intentional use of available width. For large permission matrices, a wider workspace may be preferable.
- Permission groups use emoji as category icons and derive labels from permission names. This is functional but not a stable semantic UI system.
- Server authorization must be completed before `@can`/disabled states are added for UX.
- A large permission catalog may benefit from search/filter, select-group controls, and clearer protected-role presentation.

UI quality is not the main blocker; authorization architecture is.

## Cross-Module Dependencies

Declared: `User`.

Observed but undeclared at module-manifest level: `Shared` import/export foundation.

Role also depends on application-level module permission registry services. This is acceptable as repository infrastructure, but the manifest/type conventions should accurately describe the dependency graph.

## Technical Debt

1. Role manifest says `shell`, while bootstrap architecture describes Role as support.
2. Sensitive Role workflows are split between Livewire and `ImportExport` rather than a canonical Role service.
3. Permission generation/import can create capabilities outside active module manifests.
4. Migration filenames remain malformed.
5. Placeholder public API route remains.
6. Existing `REFACTOR_PLAN.md` and `REBUILD_SPEC.md` were generated against older behavior and must not be treated as current source truth without refresh.

## Test Coverage

No Role-specific test coverage was proven during this GitHub-source re-analysis. This must be verified locally because repository search through the connector may not index every test path reliably.

Minimum regression suite recommended before implementation:

- unauthenticated Role routes denied,
- authenticated admin without each capability denied,
- authorized view/create/edit/delete paths allowed,
- direct Livewire mutation authorization tests,
- Super Admin rename/delete/mutation denied for non-system actors,
- permission-catalog tampering rejected,
- import arbitrary permission/guard rejection,
- import transaction rollback,
- role permission sync including empty active selection,
- bulk-delete protected/in-use roles,
- route compatibility redirects,
- fresh migration smoke.

## Documentation Drift

The previous `ANALYSIS.md` is stale in multiple material areas:

- It says `RoleTable` contains JSON import/export; current source uses `Services/ImportExport.php` with shared FastExcel infrastructure.
- It says selection/reset hooks were missing; they now exist.
- It says RoleForm loads all guards; it now filters admin permissions and uses `ModulePermissionManager`.
- It does not reflect the current plural `/admin/roles` canonical routes with legacy redirects.
- It predates `import_role` and `export_role` manifest permissions.

`INFORMATION.md` and `README.md` were missing before this refresh.

## Issue List

### P0

1. Capability-specific authorization missing on Role web routes and Livewire mutation actions.
2. `Super Admin` can be renamed/edited through RoleForm; this can break the global Gate bypass invariant.

### P1

1. Role mutation/delete business rules remain in Livewire instead of a canonical Role service.
2. Permission generation can create undeclared arbitrary capabilities.
3. Import can create arbitrary permissions and accepts arbitrary non-empty guard names.
4. Role save + permission sync is not transactional.
5. Bulk delete is not transactional.
6. Role manifest type conflicts with bootstrap architecture intent; Shared dependency is undeclared.
7. Negative-year migration filenames and `module_migrations` ownership require migration-hygiene review.
8. Targeted Role security/regression tests are not proven.

### P2

1. Placeholder public API endpoint.
2. Old commented route scaffolding.
3. UI width/category-label polish and permission matrix search/group actions.

## Module Health Summary

- Registration/boot: **Good, with manifest architecture drift**.
- Routes/controllers: **Structurally simple, authorization inadequate**.
- Livewire: **Functional but too much sensitive domain workflow**.
- Import/export: **Good architectural direction; catalog restrictions still needed**.
- Database/migrations: **Working legacy structure with hygiene debt**.
- UI/UX: **Generally good; secondary to security work**.
- Tests: **Insufficiently proven**.
- Overall: **Major Refactor required**.

## Final Recommendation

**Major Refactor**.

Do not full-rebuild the module. Preserve public routes, Spatie tables, Livewire aliases, Shared import/export integration and existing UI where practical. Refactor around these priorities:

1. authorization containment,
2. immutable protected-role rules,
3. canonical Role service for create/update/delete/query,
4. server-approved permission catalog,
5. import guard/catalog hardening,
6. transactional writes,
7. targeted regression/security tests,
8. manifest/dependency/migration documentation cleanup.

## Open Questions / Unknowns

- Whether local/CI already contains Role tests not visible through connector search.
- Whether any external callers depend on the public `/api/role` placeholder.
- Whether changing Role manifest from `shell` to `support` affects enabled-state/boot policy in deployed environments.
- Whether `module_migrations` has active runtime consumers outside Role.
- Exact UI wiring for the shared import/export panel should be verified before refactor implementation.

No application source code was modified by this analysis.